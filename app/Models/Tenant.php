<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $guarded = [];

    /** Müşteri (paydaş) türleri: kod => ekran adı. Yeni müşteri kaydı, düzenleme ve doğrulamalar buradan beslenir. */
    public const TURLER = [
        'eczane' => 'Eczane',
        'hastane' => 'Hastane',
        'depo' => 'Depo',
        'uretici' => 'Üretici',
        'ihracatci' => 'İhracatçı',
        'geri_odeme_kurumu' => 'Geri Ödeme Kurumu',
        'ihracata_yetkili' => 'İhracata Yetkili Firma',
        'ruhsat_sahibi' => 'Ruhsat Sahibi Firma',
        'yurtdisi_tedarikci' => 'Yurt Dışı İlaç Tedarikçisi',
    ];

    /** İhracat bildirimini yapabilen türler (kılavuz §4: üretici, ecza deposu, ihracatçı depo; ihracata yetkili firma da dahil). */
    public const IHRACAT_TURLERI = ['depo', 'uretici', 'ihracatci', 'ihracata_yetkili'];

    protected function casts(): array
    {
        return [
            'lisans_baslangic' => 'date',
            'lisans_bitis' => 'date',
            'aktif' => 'boolean',
            'bitince_salt_okunur' => 'boolean',
            'kvkk_onay_tarihi' => 'datetime',
        ];
    }

    /** Ünvan standardı: her kelimenin yalnızca baş harfi büyük. */
    protected function unvan(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(set: fn ($v) => \App\Support\Metin::baslik((string) $v));
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function itsCredential(): HasOne
    {
        return $this->hasOne(ItsCredential::class);
    }

    /** Ecza depoları sarf (hastane sarf) bildirimi yapamaz; yalnızca tüketim noktaları (hastane, eczane vb.) yapar. */
    public function sarfYapabilir(): bool
    {
        return $this->tur !== 'depo';
    }

    /** Hastaneler (sarf merkezleri) satış yapmaz; yalnızca sarf ve iade bildirir. */
    public function satisYapabilir(): bool
    {
        return $this->tur !== 'hastane';
    }

    /** Mal Devir / Mal Devir İptal şubeli müşteriler içindir; ecza depoları ve hastaneler kullanmaz. */
    public function devirYapabilir(): bool
    {
        return ! in_array($this->tur, ['depo', 'hastane'], true);
    }

    /** İhracat / İhracat İptal yalnızca ecza depoları içindir (kılavuz §4-5: üretici, ecza deposu, ihracatçı depo). */
    public function ihracatYapabilir(): bool
    {
        return in_array($this->tur, self::IHRACAT_TURLERI, true);
    }

    public function lisansAktifMi(): bool
    {
        return $this->aktif && $this->lisans_bitis->endOfDay()->isFuture();
    }

    public function kalanGun(): int
    {
        return max(0, (int) now()->startOfDay()->diffInDays($this->lisans_bitis->startOfDay(), false));
    }
}
