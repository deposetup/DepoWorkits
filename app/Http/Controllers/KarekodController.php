<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Services\ItsClient;
use App\Services\ItsIntegrationException;
use App\Support\Karekod;
use Illuminate\Http\Request;

/**
 * Karekod sorgulama: GTIN + SN ile ya da karekod okutarak ürünün İTS'deki
 * durumunu ve son sahibini gösterir (İTS Durum Sorgulama servisi).
 */
class KarekodController extends Controller
{
    public function index(Request $request)
    {
        return $this->page($request);
    }

    public function search(Request $request, ItsClient $itsClient)
    {
        $user = $request->user();

        $data = $request->validate([
            'depot_id' => $user->isAdmin() ? ['required', 'exists:depots,id'] : ['nullable'],
            'karekod' => ['nullable', 'string', 'max:200'],
            'gtin' => ['required_without:karekod', 'nullable', 'digits_between:13,14'],
            'sn' => ['required_without:karekod', 'nullable', 'string', 'max:20'],
        ]);

        $depot = $user->isAdmin() ? Depot::findOrFail($data['depot_id']) : $user->depot;
        abort_unless($depot, 403);

        if (filled($data['karekod'] ?? null)) {
            $product = Karekod::parse($data['karekod']);

            if (! $product) {
                return back()->withInput()->withErrors(['karekod' => 'Karekod okunamadı.']);
            }
        } else {
            $product = [
                'gtin' => str_pad($data['gtin'], 14, '0', STR_PAD_LEFT),
                'sn' => $data['sn'],
            ];
        }

        try {
            $result = $itsClient->checkStatus($depot, [$product])[0] ?? [];
        } catch (ItsIntegrationException $e) {
            return $this->page($request, error: $e->getMessage());
        }

        return $this->page($request, $depot, $product, $result);
    }

    protected function page(Request $request, ?Depot $depot = null, ?array $product = null, ?array $result = null, ?string $error = null)
    {
        $depots = $request->user()->isAdmin() ? Depot::orderBy('company_title')->get() : collect();

        return view('karekod.index', compact('depots', 'depot', 'product', 'result', 'error'));
    }
}
