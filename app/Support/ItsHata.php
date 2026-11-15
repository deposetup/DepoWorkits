<?php

namespace App\Support;

/** İTS hata/onay kodlarının Türkçe açıklaması (config/its_hata_kodlari.php). */
class ItsHata
{
    public static function aciklama(?string $kod): ?string
    {
        if ($kod === null || $kod === '') { return null; }

        return config('its_hata_kodlari.'.$kod);
    }

    public static function basarili(?string $uc): bool
    {
        return $uc === null || $uc === '' || $uc === '00000';
    }
}
