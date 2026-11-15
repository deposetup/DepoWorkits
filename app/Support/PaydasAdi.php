<?php

namespace App\Support;

use App\Models\Tenant;
use App\Services\Its\ItsClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** GLN'lerin İTS paydaş listesindeki ünvanını bulur (cari olmayanlar için). Eczane listesi çok büyük olduğundan taranmaz. */
class PaydasAdi
{
    private const TIPLER = ['depo', 'uretici', 'ihracatci', 'hastane'];

    /** @return array<string,array{unvan:string,tip:string}> bulunanlar */
    public static function coz(array $glnler, Tenant $tenant, ItsClient $its): array
    {
        $glnler = array_values(array_unique(array_filter($glnler, fn ($g) => preg_match('/^\d{13}$/', (string) $g))));
        $bulunan = [];
        if (! $glnler) {
            return $bulunan;
        }
        // 1) Yerel depo anlık listesi (paydaş izleme)
        if (Schema::hasTable('paydas_anliklari')) {
            foreach (DB::table('paydas_anliklari')->whereIn('gln', $glnler)->get(['gln', 'unvan', 'tip']) as $r) {
                $bulunan[$r->gln] = ['unvan' => Metin::baslik($r->unvan), 'tip' => $r->tip];
            }
        }
        // 2) Kalanlar için İTS paydaş listeleri (6 saat önbellekli gln => ünvan haritası)
        foreach (self::TIPLER as $tip) {
            if (! array_diff($glnler, array_keys($bulunan))) {
                break;
            }
            try {
                $harita = Cache::remember('paydas-harita:'.$tip, now()->addHours(6), function () use ($its, $tenant, $tip) {
                    @set_time_limit(180);

                    return collect($its->paydasListesi($tenant, $tip, true))->pluck('companyName', 'gln')->all();
                });
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
            foreach ($glnler as $g) {
                if (! isset($bulunan[$g]) && isset($harita[$g])) {
                    $bulunan[$g] = ['unvan' => Metin::baslik((string) $harita[$g]), 'tip' => $tip];
                }
            }
        }

        return $bulunan;
    }
}
