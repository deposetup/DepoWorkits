<?php

namespace App\Support;

use App\Models\IlacRecete;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** İlaç.xlsx dosyasını okuyup ilac_receteleri tablosunu tamamen yeniler. */
class IlacReceteYukleyici
{
    /**
     * @return array{okunan:int,yazilan:int,atlanan:int,tipler:array<string,int>}
     */
    public static function yukle(string $yol): array
    {
        $kayitlar = [];
        $okunan = $atlanan = 0;
        $basliklar = null;

        foreach (XlsxOkuyucu::satirlar($yol) as $satir) {
            if ($basliklar === null) {
                $basliklar = self::basliklariBul(array_keys($satir));
            }
            $okunan++;
            $gtin = IlacRecete::anahtar($satir[$basliklar['gtin']] ?? '');
            if (! $gtin) {
                $atlanan++;
                continue;
            }
            $tip = trim((string) ($satir[$basliklar['tip']] ?? ''));
            $kayitlar[$gtin] = [ // aynı GTIN iki kez varsa sonuncusu geçerli
                'gtin' => $gtin,
                'ad' => $basliklar['ad'] ? mb_substr(trim((string) ($satir[$basliklar['ad']] ?? '')), 0, 500) : null,
                'recete_tipi' => $tip !== '' ? mb_substr($tip, 0, 20) : null,
            ];
        }
        if ($basliklar === null) {
            throw new RuntimeException('Dosya boş.');
        }
        if (! $kayitlar) {
            throw new RuntimeException('Dosyada geçerli GTIN bulunamadı; mevcut liste korundu.');
        }

        $simdi = now();
        DB::transaction(function () use ($kayitlar, $simdi) {
            IlacRecete::query()->delete();
            foreach (array_chunk(array_values($kayitlar), 500) as $parca) {
                IlacRecete::insert(array_map(fn ($k) => $k + ['created_at' => $simdi, 'updated_at' => $simdi], $parca));
            }
        });

        $tipler = [];
        foreach ($kayitlar as $k) {
            $ad = $k['recete_tipi'] ?? '(boş)';
            $tipler[$ad] = ($tipler[$ad] ?? 0) + 1;
        }
        arsort($tipler);

        return ['okunan' => $okunan, 'yazilan' => count($kayitlar), 'atlanan' => $atlanan, 'tipler' => $tipler];
    }

    /** @param array<int,string> $adlar @return array{gtin:string,tip:string,ad:?string} */
    private static function basliklariBul(array $adlar): array
    {
        $norm = fn (string $a) => str_replace(['ı', 'İ', 'ç', 'Ç', 'ş', 'Ş', 'ğ', 'Ğ', 'ö', 'Ö', 'ü', 'Ü'], ['i', 'i', 'c', 'c', 's', 's', 'g', 'g', 'o', 'o', 'u', 'u'], mb_strtolower($a === 'I' ? 'ı' : $a, 'UTF-8'));
        $bul = fn (array $adaylar) => collect($adlar)->first(fn ($a) => in_array($norm(trim($a)), $adaylar, true));
        $gtin = $bul(['gtin']);
        $tip = $bul(['recete tipi', 'recetetipi']);
        if (! $gtin || ! $tip) {
            throw new RuntimeException('Dosyada "GTIN" ve "Reçete Tipi" sütunları bulunamadı. Bulunan sütunlar: '.implode(', ', array_slice($adlar, 0, 12)));
        }

        return ['gtin' => $gtin, 'tip' => $tip, 'ad' => $bul(['adi', 'ad', 'ilac adi'])];
    }
}
