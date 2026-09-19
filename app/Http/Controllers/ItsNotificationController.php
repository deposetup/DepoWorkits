<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\ItsNotification;
use App\Services\ItsClient;
use App\Services\ItsIntegrationException;
use Illuminate\Http\Request;

class ItsNotificationController extends Controller
{
    /**
     * Bu ekranlardan oluşturulabilen bildirim türleri. Diğer türler
     * (satış, ihracat, üretim vb.) henüz UI'dan desteklenmiyor.
     */
    private const CREATABLE_TYPES = [
        ItsNotification::TYPE_ALIM,
        ItsNotification::TYPE_IPTAL_IADE,
    ];

    /**
     * Admin: tüm depoların bildirimlerini görür. Müşteri: sadece kendi deposunun.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = ItsNotification::with('depot')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('depot_id', $user->depot_id))
            ->latest()
            ->paginate(20);

        return view('its-notifications.index', compact('notifications'));
    }

    public function create(Request $request, string $type)
    {
        abort_unless(in_array($type, self::CREATABLE_TYPES, true), 404);

        $user = $request->user();
        $depots = $user->isAdmin() ? Depot::orderBy('company_title')->get() : collect();

        return view('its-notifications.create', [
            'type' => $type,
            'depots' => $depots,
        ]);
    }

    public function store(Request $request, ItsClient $itsClient)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', self::CREATABLE_TYPES)],
            'depot_id' => $user->isAdmin() ? ['required', 'exists:depots,id'] : ['nullable'],
            'togln' => ['required_if:type,'.ItsNotification::TYPE_IPTAL_IADE, 'nullable', 'string', 'max:20'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.gtin' => ['required', 'string', 'max:20'],
            'products.*.sn' => ['required', 'string', 'max:20'],
            'products.*.bn' => ['required', 'string', 'max:20'],
            'products.*.xd' => ['required', 'date'],
        ]);

        $depotId = $user->isAdmin() ? $data['depot_id'] : $user->depot_id;
        abort_unless($depotId, 403);

        $products = collect($data['products'])->map(fn ($product) => [
            'gtin' => $product['gtin'],
            'sn' => $product['sn'],
            'bn' => $product['bn'],
            'xd' => $product['xd'],
        ])->all();

        $payload = $data['type'] === ItsNotification::TYPE_IPTAL_IADE
            ? ['togln' => $data['togln'], 'productList' => $products]
            : ['productList' => $products];

        $notification = ItsNotification::create([
            'depot_id' => $depotId,
            'type' => $data['type'],
            'payload' => $payload,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        try {
            $response = $itsClient->send($notification);

            $notification->update([
                'status' => 'sent',
                'its_response' => $response,
                // Not: kılavuzun alan tablosu "notification_id" der, ancak tüm
                // örnek yanıtlarda tutarlı şekilde "notificationid" (alt çizgisiz)
                // kullanılmış; gerçek API yanıtı doğrulanınca netleştirilecek.
                'its_reference' => $response['notificationid'] ?? $response['notification_id'] ?? null,
                'attempts' => $notification->attempts + 1,
            ]);

            return redirect()->route('its-notifications.index')
                ->with('status', 'Bildirim İTS\'ye başarıyla gönderildi.');
        } catch (ItsIntegrationException $e) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'attempts' => $notification->attempts + 1,
            ]);

            return back()->withInput()->with('status', 'Bildirim gönderilemedi: '.$e->getMessage());
        }
    }
}
