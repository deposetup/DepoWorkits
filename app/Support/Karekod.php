<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * İlaç kutusundaki GS1 karekodunu (DataMatrix) ayrıştırır.
 *
 * Alanlar: (01) GTIN 14 hane, (21) sıra no, (17) SKT YYAAGG, (10) parti no.
 * Okuyucu değişken uzunluklu alanlardan sonra GS (\x1D) karakteri gönderir;
 * bazı okuyucular göndermez, o durumda 01-21-17-10 sırası varsayılır.
 *
 * @see Beşeri Tıbbi Ürünler Barkod ve Karekod Uygulama Kılavuzu (its.gov.tr)
 */
class Karekod
{
    /**
     * @return array{gtin: string, sn: string, xd?: string, bn?: string}|null
     */
    public static function parse(string $raw): ?array
    {
        $raw = trim(preg_replace('/^\]d2/', '', $raw));

        $fields = str_contains($raw, "\x1D")
            ? self::parseWithSeparator($raw)
            : self::parseWithoutSeparator($raw);

        if (! isset($fields['gtin'], $fields['sn'])) {
            return null;
        }

        if (isset($fields['xd'])) {
            $fields['xd'] = self::expiryDate($fields['xd']);
        }

        return array_filter($fields);
    }

    protected static function parseWithSeparator(string $raw): array
    {
        $fields = [];
        $rest = $raw;

        while ($rest !== '') {
            $ai = substr($rest, 0, 2);
            $rest = substr($rest, 2);

            if ($ai === '01' || $ai === '17') {
                $length = $ai === '01' ? 14 : 6;
                $fields[$ai === '01' ? 'gtin' : 'xd'] = substr($rest, 0, $length);
                $rest = ltrim(substr($rest, $length), "\x1D");
            } elseif ($ai === '21' || $ai === '10') {
                [$value, $rest] = array_pad(explode("\x1D", $rest, 2), 2, '');
                $fields[$ai === '21' ? 'sn' : 'bn'] = $value;
            } else {
                return [];
            }
        }

        return $fields;
    }

    protected static function parseWithoutSeparator(string $raw): array
    {
        if (! preg_match('/^01(\d{14})21(.{1,20}?)17(\d{6})10(.{1,20})$/', $raw, $m)) {
            return [];
        }

        return ['gtin' => $m[1], 'sn' => $m[2], 'xd' => $m[3], 'bn' => $m[4]];
    }

    /**
     * YYAAGG → Y-m-d. GS1'e göre gün "00" ise ayın son günüdür.
     */
    protected static function expiryDate(string $yymmdd): string
    {
        $date = Carbon::createFromFormat('!ymd', substr($yymmdd, 0, 4).'01');

        return substr($yymmdd, 4, 2) === '00'
            ? $date->endOfMonth()->toDateString()
            : $date->setDay((int) substr($yymmdd, 4, 2))->toDateString();
    }
}
