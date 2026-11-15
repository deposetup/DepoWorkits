<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Its\ItsClient;
use App\Support\Metin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * GLN yazılınca ünvan, il, ilçe vb. bilgileri İTS Paydaş Listesi servisinden (reference/app/stakeholder) getirir.
 * Serviste GLN ile doğrudan sorgu yoktur; paydaş tipleri sırayla taranır (en büyük liste olan eczane sona bırakılır).
 */
class GlnSorguController extends Controller
{
    private const SIRA = ['depo', 'hastane', 'uretici', 'ihracatci', 'eczane'];

    public function __invoke(Request $request, ItsClient $its)
    {
        $v = $request->validate(['gln' => 'required|digits:13', 'tur' => 'nullable|in:'.implode(',', self::SIRA)]);
        $gln = $v['gln'];
        $tur = $v['tur'] ?? null;

        $onbellek = 'gln-sorgu:'.$gln.':'.($tur ?? '-');
        if (($c = Cache::get($onbellek)) !== null) {
            return response()->json($c);
        }

        // İTS çağrısı için herhangi bir müşterinin kayıtlı İTS bilgisi kullanılır (paydaş listesi herkese açık referans veridir).
        $kimlik = Tenant::whereHas('itsCredential')->first();
        if (! $kimlik && config('its.driver') !== 'rest') {
            $kimlik = new Tenant();
        }
        if (! $kimlik) {
            return response()->json(['bulundu' => false, 'hata' => 'GLN sorgusu için en az bir müşteriye İTS kullanıcı bilgisi girilmiş olmalı.']);
        }

        @set_time_limit(180);
        foreach ($tur ? [$tur] : self::SIRA as $tip) {
            try {
                $kayit = collect($its->paydasListesi($kimlik, $tip, true))->firstWhere('gln', $gln);
            } catch (\Throwable $e) {
                return response()->json(['bulundu' => false, 'hata' => 'İTS sorgusu başarısız: '.mb_substr($e->getMessage(), 0, 200)]);
            }
            if ($kayit) {
                $c = [
                    'bulundu' => true, 'tur' => $tip, 'aktif' => (bool) $kayit['active'],
                    'unvan' => Metin::baslik((string) $kayit['companyName']),
                    'il' => Metin::baslik((string) ($kayit['city'] ?? '')), 'ilce' => Metin::baslik((string) ($kayit['town'] ?? '')),
                    'eposta' => filter_var($kayit['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
                    'telefon' => preg_match('/^[0-9+()\s-]{7,30}$/', (string) ($kayit['phone'] ?? '')) ? $kayit['phone'] : null,
                    'yetkili' => $kayit['authorized'] ?? null,
                ];
                Cache::put($onbellek, $c, now()->addDay());

                return response()->json($c);
            }
        }
        $c = ['bulundu' => false, 'hata' => 'Bu GLN İTS paydaş listesinde bulunamadı.'];
        Cache::put($onbellek, $c, now()->addMinutes(10));

        return response()->json($c);
    }
}
