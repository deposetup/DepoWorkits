<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/** PTS dosyasını (zip ya da tek XML) bellekte açar; GetPackage yanıtı ve elle yüklenen dosyalar için ortak. */
class PtsDosya
{
    private const TOPLAM_LIMIT = 50 * 1024 * 1024;

    /** @return array<string,string> dosya adı => XML içeriği */
    public static function xmllerCikar(string $bayt): array
    {
        if (str_starts_with($bayt, 'PK')) {
            $gecici = tempnam(sys_get_temp_dir(), 'pts');
            file_put_contents($gecici, $bayt);
            $zip = new ZipArchive();
            if ($zip->open($gecici) !== true) {
                @unlink($gecici);
                throw new RuntimeException('Zip dosyası açılamadı.');
            }
            $xmller = [];
            $toplam = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $st = $zip->statIndex($i);
                if (! str_ends_with(strtolower($st['name']), '.xml')) { continue; }
                $toplam += $st['size'];
                if ($toplam > self::TOPLAM_LIMIT) {
                    $zip->close(); @unlink($gecici);
                    throw new RuntimeException('Paket çok büyük (açılmış hâli 50 MB üstü).');
                }
                $xmller[basename($st['name'])] = $zip->getFromIndex($i); // yalnız ad bazlı; yola yazılmaz
            }
            $zip->close();
            @unlink($gecici);
        } else {
            $xmller = ['paket.xml' => $bayt];
        }
        if (! $xmller) {
            throw new RuntimeException('Dosyada XML bulunamadı.');
        }

        return $xmller;
    }
}
