<?php

namespace App\Support;

use App\Models\IlacFiyat;
use App\Models\IlacRecete;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** "İlaç Geçerli Fiyat Listesi" .xlsx dosyasını okuyup ilac_fiyatlari tablosunu tamamen yeniler. */
class IlacFiyatYukleyici
{
    /** Excel başlığı (sadeleştirilmiş) => tablo sütunu. */
    private const SUTUNLAR = [
        'ilac gtin' => 'gtin', 'gtin' => 'gtin',
        'ilac adi' => 'ad', 'adi' => 'ad',
        'uretici' => 'uretici',
        'aktif?' => 'aktif', 'aktif' => 'aktif',
        'ithal?' => 'ithal', 'ithal' => 'ithal',
        'gecerlilik tarihi' => 'gecerlilik_tarihi',
        'degisiklik tarihi' => 'degisiklik_tarihi',
        'isf' => 'isf', 'dsf' => 'dsf',
        'psf (kdv haric)' => 'psf_kdvsiz', 'psf' => 'psf',
        'kurum isk' => 'kurum_isk', 'ozel isk' => 'ozel_isk',
    ];

    /** @return array{okunan:int,yazilan:int,atlanan:int,bulunmayan:array<int,string>} */
    public static function yukle(string $yol): array
    {
        $kayitlar = [];
        $okunan = $atlanan = 0;
        $harita = null;
        $simdi = now();

        foreach (XlsxOkuyucu::satirlar($yol) as $satir) {
            if ($harita === null) {
                $harita = self::haritaCikar(array_keys($satir));
            }
            $okunan++;
            $ham = [];
            foreach ($harita as $baslik => $sutun) {
                $ham[$sutun] = $satir[$baslik] ?? '';
            }
            $gtin = IlacRecete::anahtar($ham['gtin'] ?? '');
            if (! $gtin) {
                $atlanan++;
                continue;
            }
            $kayitlar[$gtin] = [ // aynı GTIN iki kez varsa sonuncusu geçerli
                'gtin' => $gtin,
                'ad' => self::metin($ham['ad'] ?? '', 500),
                'uretici' => self::metin($ham['uretici'] ?? '', 500),
                'aktif' => self::mantik($ham['aktif'] ?? '1'),
                'ithal' => self::mantik($ham['ithal'] ?? '0'),
                'gecerlilik_tarihi' => self::tarih($ham['gecerlilik_tarihi'] ?? ''),
                'degisiklik_tarihi' => self::tarih($ham['degisiklik_tarihi'] ?? ''),
                'isf' => self::sayi($ham['isf'] ?? ''), 'dsf' => self::sayi($ham['dsf'] ?? ''),
                'psf_kdvsiz' => self::sayi($ham['psf_kdvsiz'] ?? ''), 'psf' => self::sayi($ham['psf'] ?? ''),
                'kurum_isk' => self::sayi($ham['kurum_isk'] ?? ''), 'ozel_isk' => self::sayi($ham['ozel_isk'] ?? ''),
                'created_at' => $simdi, 'updated_at' => $simdi,
            ];
        }
        if ($harita === null) {
            throw new RuntimeException('Dosya boş.');
        }
        if (! $kayitlar) {
            throw new RuntimeException('Dosyada geçerli GTIN bulunamadı; mevcut liste korundu.');
        }

        DB::transaction(function () use ($kayitlar) {
            IlacFiyat::query()->delete();
            foreach (array_chunk(array_values($kayitlar), 500) as $parca) {
                IlacFiyat::insert($parca);
            }
        });

        return ['okunan' => $okunan, 'yazilan' => count($kayitlar), 'atlanan' => $atlanan];
    }

    /** @param array<int,string> $basliklar @return array<string,string> Excel başlığı => sütun */
    private static function haritaCikar(array $basliklar): array
    {
        $harita = [];
        foreach ($basliklar as $b) {
            $anahtar = self::sade($b);
            if (isset(self::SUTUNLAR[$anahtar]) && ! in_array(self::SUTUNLAR[$anahtar], $harita, true)) {
                $harita[$b] = self::SUTUNLAR[$anahtar];
            }
        }
        if (! in_array('gtin', $harita, true)) {
            throw new RuntimeException('Dosyada "İlaç GTIN" sütunu bulunamadı. Bulunan sütunlar: '.implode(', ', array_slice($basliklar, 0, 14)));
        }

        return $harita;
    }

    private static function sade(string $a): string
    {
        $a = str_replace('I', 'ı', trim($a)); // büyük I → ı (mb_strtolower Türkçe bilmez)
        $a = str_replace('İ', 'i', $a);

        return str_replace(['ı', 'ç', 'ş', 'ğ', 'ö', 'ü'], ['i', 'c', 's', 'g', 'o', 'u'], mb_strtolower($a, 'UTF-8'));
    }

    private static function metin(string $v, int $en): ?string
    {
        $v = trim($v);

        return $v === '' ? null : mb_substr($v, 0, $en);
    }

    private static function mantik(string $v): bool
    {
        return in_array(mb_strtolower(trim($v)), ['1', 'true', 'evet', 'e', 'x', 'yes'], true);
    }

    private static function sayi(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (str_contains($v, ',')) { // 1.234,56 biçimi
            $v = str_replace(['.', ','], ['', '.'], $v);
        }

        return is_numeric($v) ? number_format((float) $v, 2, '.', '') : null;
    }

    /** Excel seri numarası (46183) ya da metin tarih (2026-06-12, 12.06.2026). */
    private static function tarih(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        try {
            if (is_numeric($v)) {
                return (float) $v > 1 ? Carbon::create(1899, 12, 30)->addDays((int) $v)->toDateString() : null;
            }

            return preg_match('/^\d{1,2}[.\/]\d{1,2}[.\/]\d{4}/', $v) ? Carbon::createFromFormat('d.m.Y', substr(str_replace('/', '.', $v), 0, 10))->toDateString() : Carbon::parse($v)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
