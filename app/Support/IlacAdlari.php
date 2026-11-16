<?php

namespace App\Support;

use App\Services\Its\ItsClient;
use Illuminate\Support\Facades\Cache;

/** GTIN => ilaç adı haritası (StokController ile aynı önbellek). */
class IlacAdlari
{
    public static function harita(): array
    {
        $tenant = auth()->user()?->tenant;
        if (! $tenant) {
            return [];
        }
        try {
            return Cache::remember('ilac_adlari_'.$tenant->id, now()->addHours(6),
                fn () => collect(app(ItsClient::class)->ilacListesi($tenant))->pluck('drugName', 'gtin')->all());
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** Okunabilir (parantezli) ya da ham GS1 metninden GTIN'i ayıklar. */
    public static function gtinBul(string $kod): ?string
    {
        return preg_match('/^\(?01\)?(\d{14})/', trim($kod), $m) ? $m[1] : null;
    }

    /** Ekranda gösterim için parantezleri kaldırır. */
    public static function parantezsiz(string $kod): string
    {
        return str_replace(['(', ')'], '', $kod);
    }
}
