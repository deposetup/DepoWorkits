<?php

namespace App\Support;

use Generator;
use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Bağımlılıksız, akışlı (bellek dostu) minimal .xlsx okuyucu: ilk sayfayı okur,
 * ilk satırı başlık kabul eder ve her satırı [başlık => değer] dizisi olarak verir.
 * Dosya açılmaz/çıkarılmaz; yalnızca zip içindeki XML parçaları okunur.
 */
class XlsxOkuyucu
{
    /** Sayfa XML'i için kabul edilen en büyük açılmış boyut (zip bombasına karşı). */
    private const EN_FAZLA_ACILMIS = 300 * 1024 * 1024;

    /** @return Generator<int,array<string,string>> */
    public static function satirlar(string $yol): Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($yol) !== true) {
            throw new RuntimeException('Dosya geçerli bir .xlsx (Excel) dosyası değil.');
        }
        try {
            $sayfa = self::ilkSayfa($zip);
            foreach (['xl/sharedStrings.xml', $sayfa] as $parca) {
                $st = $zip->statName($parca);
                if ($st && $st['size'] > self::EN_FAZLA_ACILMIS) {
                    throw new RuntimeException('Excel dosyası beklenenden çok büyük.');
                }
            }
            $metinler = self::ortakMetinler($zip);

            $x = new XMLReader();
            $x->XML($zip->getFromName($sayfa), null, LIBXML_NONET | LIBXML_COMPACT);
            $baslik = null;
            while ($x->read()) {
                if ($x->nodeType !== XMLReader::ELEMENT || $x->name !== 'row') {
                    continue;
                }
                $satir = simplexml_load_string($x->readOuterXml(), 'SimpleXMLElement', LIBXML_NONET);
                $hucre = [];
                foreach ($satir->c as $c) {
                    $sutun = preg_replace('/\d+/', '', (string) $c['r']);
                    $hucre[$sutun] = self::deger($c, $metinler);
                }
                if ($baslik === null) {
                    $baslik = array_map('trim', $hucre);
                    continue;
                }
                $kayit = [];
                foreach ($baslik as $sutun => $ad) {
                    $kayit[$ad] = $hucre[$sutun] ?? '';
                }
                yield $kayit;
            }
        } finally {
            $zip->close();
        }
    }

    private static function ilkSayfa(ZipArchive $zip): string
    {
        $adaylar = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $ad = $zip->getNameIndex($i);
            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $ad)) {
                $adaylar[] = $ad;
            }
        }
        if (! $adaylar) {
            throw new RuntimeException('Excel dosyasında sayfa bulunamadı.');
        }
        natsort($adaylar);

        return array_values($adaylar)[0];
    }

    /** @return array<int,string> */
    private static function ortakMetinler(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $liste = [];
        $x = new XMLReader();
        $x->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        while ($x->read()) {
            if ($x->nodeType === XMLReader::ELEMENT && $x->name === 'si') {
                $si = simplexml_load_string($x->readOuterXml(), 'SimpleXMLElement', LIBXML_NONET);
                $m = '';
                foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $t) {
                    $m .= (string) $t;
                }
                $liste[] = $m;
            }
        }

        return $liste;
    }

    private static function deger(\SimpleXMLElement $c, array $metinler): string
    {
        $tip = (string) $c['t'];
        if ($tip === 's') {
            return $metinler[(int) $c->v] ?? '';
        }
        if ($tip === 'inlineStr') {
            $m = '';
            foreach ($c->xpath('.//*[local-name()="t"]') ?: [] as $t) {
                $m .= (string) $t;
            }

            return $m;
        }

        return (string) $c->v;
    }
}
