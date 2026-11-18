<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Tüm müşteriler için ortak geçerli ilaç fiyat listesi; yalnızca yönetici yükler. */
class IlacFiyat extends Model
{
    protected $table = 'ilac_fiyatlari';
    protected $primaryKey = 'gtin';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'ithal' => 'boolean', 'gecerlilik_tarihi' => 'date', 'degisiklik_tarihi' => 'date'];
    }

    public static function hazir(): bool
    {
        return Schema::hasTable('ilac_fiyatlari');
    }

    /** Veride Türkçe karakter olmadığı için arama metni ASCII'ye indirilir (ş→s, ı→i ...). */
    public static function aramaMetni(string $ara): string
    {
        return trim(strtr($ara, ['ı' => 'i', 'İ' => 'I', 'ş' => 's', 'Ş' => 'S', 'ğ' => 'g', 'Ğ' => 'G', 'ü' => 'u', 'Ü' => 'U', 'ö' => 'o', 'Ö' => 'O', 'ç' => 'c', 'Ç' => 'C']));
    }

    /** İlaç adı, üretici ya da barkod (GTIN) içinde arar. */
    public static function ara(string $ara): Builder
    {
        $metin = self::aramaMetni($ara);
        $rakam = preg_replace('/\D/', '', $ara);
        $kacis = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $metin);

        return self::query()->where(function ($q) use ($kacis, $rakam, $ara) {
            $q->where('ad', 'like', '%'.$kacis.'%')->orWhere('uretici', 'like', '%'.$kacis.'%');
            if ($rakam !== '' && $rakam === trim($ara)) {
                $q->orWhere('gtin', 'like', '%'.ltrim($rakam, '0').'%');
            }
        })->orderBy('ad');
    }
}
