<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Services\ItsClient;
use App\Services\ItsIntegrationException;
use Illuminate\Http\Request;

/**
 * PTS (Paket Transfer Sistemi) paket sorgulama ekranı.
 *
 * Paket Gönderim/Alım servisleri (iç içe paket hiyerarşisi bildirimi)
 * henüz UI'dan desteklenmiyor; şimdilik sadece salt-okunur sorgulama var.
 */
class PtsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $depots = $user->isAdmin() ? Depot::orderBy('company_title')->get() : collect();

        return view('pts.index', [
            'depots' => $depots,
            'results' => null,
            'error' => null,
        ]);
    }

    public function search(Request $request, ItsClient $itsClient)
    {
        $user = $request->user();

        $data = $request->validate([
            'depot_id' => $user->isAdmin() ? ['required', 'exists:depots,id'] : ['nullable'],
            'source_gln' => ['required', 'string', 'max:20'],
            'destination_gln' => ['required', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $depot = $user->isAdmin() ? Depot::findOrFail($data['depot_id']) : $user->depot;
        abort_unless($depot, 403);

        $depots = $user->isAdmin() ? Depot::orderBy('company_title')->get() : collect();

        try {
            $response = $itsClient->searchPackages(
                $depot,
                $data['source_gln'],
                $data['destination_gln'],
                $data['start_date'],
                $data['end_date'],
            );

            return view('pts.index', [
                'depots' => $depots,
                'results' => $response['transferDetails'] ?? [],
                'error' => null,
            ]);
        } catch (ItsIntegrationException $e) {
            return view('pts.index', [
                'depots' => $depots,
                'results' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
