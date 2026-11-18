<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** GTIN => reçete tipi (Beyaz, Kırmızı, Yeşil, Turuncu, Mor). Tüm müşteriler için ortaktır; yalnızca yönetici yükler. */
class IlacRecete extends Model
{
    protected $table = 'ilac_receteleri';
    protected $primaryKey = 'gtin';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /** Rakam dışını atar, 14 haneye soldan sıfırla tamamlar (13 haneli EAN ile 14 haneli GTIN aynı ürünü gösterir). */
    public static function anahtar(?string $gtin): ?string
    {
        $r = preg_replace('/\D/', '', (string) $gtin);
        if ($r === '' || strlen($r) < 8 || strlen($r) > 14) {
            return null;
        }

        return str_pad($r, 14, '0', STR_PAD_LEFT);
    }

    /**
     * @param iterable<string|null> $gtinler
     * @return array<string,string> verilen (özgün) GTIN => reçete tipi; yalnızca bilinenler
     */
    public static function tipler(iterable $gtinler): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('ilac_receteleri')) {
            return [];
        }
        $eslesme = [];
        foreach ($gtinler as $g) {
            if ($g !== null && ($a = self::anahtar($g))) {
                $eslesme[$g] = $a;
            }
        }
        if (! $eslesme) {
            return [];
        }
        $bulunan = [];
        foreach (array_chunk(array_unique(array_values($eslesme)), 500) as $parca) {
            $bulunan += self::whereIn('gtin', $parca)->whereNotNull('recete_tipi')->pluck('recete_tipi', 'gtin')->all();
        }
        $sonuc = [];
        foreach ($eslesme as $ozgun => $a) {
            if (isset($bulunan[$a])) {
                $sonuc[$ozgun] = $bulunan[$a];
            }
        }

        return $sonuc;
    }

    /** Stok ekranlarında gösterilen tüm tiplerin rozet sınıfı. */
    public const TUMU = ['Kırmızı' => 'text-bg-danger', 'Yeşil' => 'text-bg-success', 'Turuncu' => 'text-bg-warning', 'Mor' => 'text-bg-secondary', 'Beyaz' => 'text-bg-light border'];

    /**
     * Tüm reçete tiplerini (Kırmızı, Yeşil, Turuncu, Mor, Beyaz) renkli rozet olarak üretir.
     * Varsayılan: "… Reçete" yazılı (işlem sonuçları, PTS paketi). $hepsi = true: yalnızca tip adı (stok ekranları).
     */
    public static function rozet(?string $tip, bool $hepsi = false): string
    {
        if ($tip === null || $tip === '') {
            return '';
        }
        $sinif = self::TUMU[$tip] ?? 'text-bg-light border';
        $stil = $tip === 'Mor' ? ' style="background:#7c4dbd!important"' : '';

        return '<span class="badge '.$sinif.'"'.$stil.'>'.e($tip).($hepsi ? '' : ' Reçete').'</span>';
    }
}
