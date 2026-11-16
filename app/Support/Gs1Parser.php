<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * GS1 DataMatrix karekod ayrıştırıcı.
 * AI 01 GTIN (14), 17 SKT (YYMMDD), 10 lot (değişken), 21 seri (değişken).
 * Değişken uzunluklu alanlar GS (0x1D) ile biter.
 */
class Gs1Parser
{
    private const GS = "\x1D";

    /** @return array{gtin:string,seri:?string,lot:?string,skt:?string} */
    public static function parse(string $kod): array
    {
        $kod = trim(str_replace(['<GS>', '{GS}', '^'], self::GS, $kod));
        $kod = preg_replace('/^\]d2/', '', $kod); // sembol tanımlayıcı
        // Okunabilir biçim: (01)GTIN(21)SERI(10)LOT(17)SKT → ham biçime çevrilir (değişken alanlardan sonra GS eklenir)
        if (preg_match('/^\(\d{2,4}\)/', $kod)) {
            $kod = preg_replace_callback('/\((\d{2,4})\)([^()]*)/', function ($m) {
                return $m[1].$m[2].(in_array($m[1], ['10', '21'], true) ? self::GS : '');
            }, $kod);
        }
        $out = ['gtin' => null, 'seri' => null, 'lot' => null, 'skt' => null];

        // GS ayracı kaybolmuş (Excel/metin kopyası) ham karekod: alanlar mantıklı biçimde yeniden ayrıştırılır.
        if (! str_contains($kod, self::GS) && preg_match('/^01(\d{14})(.+)$/s', $kod, $m)) {
            $cozum = self::gsSiz($m[2], []);
            if ($cozum !== null && count($cozum) >= 2) {
                return ['gtin' => $m[1], 'seri' => $cozum['21'] ?? null, 'lot' => $cozum['10'] ?? null,
                    'skt' => isset($cozum['17']) ? self::tarih($cozum['17']) : null];
            }
        }
        $i = 0;
        $n = strlen($kod);

        while ($i < $n) {
            if ($kod[$i] === self::GS) { $i++; continue; }
            $ai = substr($kod, $i, 2);
            $i += 2;
            switch ($ai) {
                case '01':
                    $out['gtin'] = self::sabit($kod, $i, 14);
                    break;
                case '17':
                    $out['skt'] = self::tarih(self::sabit($kod, $i, 6));
                    break;
                case '10':
                    $out['lot'] = self::degisken($kod, $i, 20);
                    break;
                case '21':
                    $out['seri'] = self::degisken($kod, $i, 20);
                    break;
                default:
                    throw new InvalidArgumentException("Desteklenmeyen AI: {$ai}");
            }
        }

        if ($out['gtin'] === null) {
            throw new InvalidArgumentException('Karekodda GTIN (01) yok.');
        }

        return $out;
    }

    /**
     * GS ayracı olmayan 10/17/21 dizisini çözer. En çok alan veren, eşitlikte en kısa değişken alanlı çözümü seçer.
     *
     * @param  array<string,bool>  $kullanilan
     * @return array<string,string>|null
     */
    private static function gsSiz(string $s, array $kullanilan): ?array
    {
        if ($s === '') {
            return [];
        }
        $ai = substr($s, 0, 2);
        if (! in_array($ai, ['10', '17', '21'], true) || isset($kullanilan[$ai])) {
            return null;
        }
        $kullanilan[$ai] = true;
        if ($ai === '17') {
            $v = substr($s, 2, 6);
            if (strlen($v) !== 6 || ! ctype_digit($v) || (int) substr($v, 2, 2) < 1 || (int) substr($v, 2, 2) > 12 || (int) substr($v, 4, 2) > 31) {
                return null;
            }
            $alt = self::gsSiz(substr($s, 8), $kullanilan);

            return $alt === null ? null : [$ai => $v] + $alt;
        }
        $en = null;
        for ($l = 1, $son = min(20, strlen($s) - 2); $l <= $son; $l++) {
            $alt = self::gsSiz(substr($s, 2 + $l), $kullanilan);
            if ($alt !== null && ($en === null || count($alt) + 1 > count($en))) {
                $en = [$ai => substr($s, 2, $l)] + $alt;
            }
        }

        return $en;
    }

    private static function sabit(string $s, int &$i, int $len): string
    {
        $v = substr($s, $i, $len);
        if (strlen($v) !== $len || ! ctype_digit($v)) {
            throw new InvalidArgumentException('Sabit uzunluklu alan hatalı.');
        }
        $i += $len;

        return $v;
    }

    private static function degisken(string $s, int &$i, int $max): string
    {
        $end = strpos($s, self::GS, $i);
        $v = $end === false ? substr($s, $i) : substr($s, $i, $end - $i);
        if ($v === '' || strlen($v) > $max) {
            throw new InvalidArgumentException('Değişken uzunluklu alan hatalı.');
        }
        $i += strlen($v);

        return $v;
    }

    private static function tarih(string $yymmdd): string
    {
        $yy = (int) substr($yymmdd, 0, 2);
        $mm = substr($yymmdd, 2, 2);
        $dd = substr($yymmdd, 4, 2);
        if ($dd === '00') { // GS1: gün 00 ise ayın son günü
            $dd = date('t', mktime(0, 0, 0, (int) $mm, 1, 2000 + $yy));
        }

        return sprintf('%04d-%s-%s', 2000 + $yy, $mm, $dd);
    }
}
