<?php

namespace App\Http\Controllers;

use App\Services\Its\ItsClient;
use Illuminate\Http\Request;

class PaydasController extends Controller
{
    public const TIPLER = ['uretici' => 'Üretici', 'depo' => 'Depo', 'eczane' => 'Eczane', 'hastane' => 'Hastane', 'ihracatci' => 'İhracatçı'];
    private const LIMIT = 500;

    public function index(Request $request, ItsClient $its)
    {
        $v = $request->validate([
            'tip' => 'nullable|in:'.implode(',', array_keys(self::TIPLER)),
            'durum' => 'nullable|in:aktif,pasif,hepsi',
            'plaka' => 'nullable|integer|min:1|max:81',
            'ara' => 'nullable|string|max:100',
        ]);
        $durum = $v['durum'] ?? 'hepsi';
        $liste = null;
        $hata = null;

        if ($request->has('sorgula') && empty($v['tip'])) {
            $hata = 'Önce paydaş tipini seçin.';
        } elseif ($request->has('sorgula')) {
            try {
                $liste = collect($its->paydasListesi($request->user()->tenant, $v['tip'], $durum !== 'aktif', isset($v['plaka']) ? (int) $v['plaka'] : null))
                    ->when($durum === 'aktif', fn ($c) => $c->where('active', true))
                    ->when($durum === 'pasif', fn ($c) => $c->where('active', false))
                    ->when($ara = trim($v['ara'] ?? ''), fn ($c) => $c->filter(fn ($p) => mb_stripos($p['companyName'].' '.$p['gln'].' '.$p['city'], $ara) !== false))
                    ->sortBy('companyName')->values();
            } catch (\Throwable $e) {
                $hata = $e->getMessage();
            }
        }

        if ($liste !== null && $request->boolean('excel')) {
            return \App\Support\XlsxYazici::indir('paydaslar-'.now()->format('Ymd-His').'.xlsx',
                ['GLN', 'Ünvan', 'Tip', 'Durum', 'İl', 'İlçe', 'Yetkili', 'E-posta', 'Telefon'],
                $liste->map(fn ($p) => [$p['gln'], $p['companyName'], self::TIPLER[$p['tip']] ?? $p['tip'], $p['active'] ? 'Aktif' : 'Pasif', $p['city'], $p['town'], $p['authorized'], $p['email'], $p['phone']]),
                'Paydaşlar');
        }

        return view('paydas.index', [
            'tipler' => self::TIPLER, 'liste' => $liste, 'toplam' => $liste?->count(), 'gosterilen' => $liste?->take(self::LIMIT),
            'hata' => $hata, 'f' => ['tip' => $v['tip'] ?? 'depo', 'durum' => $durum, 'plaka' => $v['plaka'] ?? '', 'ara' => $v['ara'] ?? ''],
        ]);
    }
}
