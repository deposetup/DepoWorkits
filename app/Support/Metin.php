<?php

namespace App\Support;

class Metin
{
    /**
     * Ünvan standardı: yalnızca kelimelerin baş harfi büyük, Türkçe harf kurallarıyla (I/ı, İ/i).
     * "ECZA DEPOSU A.Ş." → "Ecza Deposu A.Ş."; bağlaç "ve" küçük kalır.
     */
    public static function baslik(string $metin): string
    {
        $metin = trim((string) preg_replace('/\s+/u', ' ', $metin));
        if ($metin === '') {
            return '';
        }
        $kucuk = mb_strtolower(strtr($metin, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
        $sonuc = preg_replace_callback('/(^|[\s.\-\/(&,])(\p{L})/u', function ($m) {
            return $m[1].mb_strtoupper(strtr($m[2], ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
        }, $kucuk);

        return (string) preg_replace('/(?<=\s)Ve(?=\s)/u', 've', $sonuc);
    }
}
