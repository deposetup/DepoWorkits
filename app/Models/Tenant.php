<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $guarded = [];

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

    /** Mal Devir / Mal Devir İptal şubeli müşteriler içindir; ecza depoları kullanmaz. */
    public function devirYapabilir(): bool
    {
        return $this->tur !== 'depo';
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
