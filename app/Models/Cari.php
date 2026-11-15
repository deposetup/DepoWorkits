<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Müşterinin (tenant) kendi cari listesi: İTS paydaş listesinden eklenen karşı paydaşlar. */
class Cari extends Model
{
    protected $table = 'cariler';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    /** GLN => ünvan eşlemesi (bu müşterinin carileri); ekranlarda GLN'in yanına cari adını yazmak için. */
    public static function adlar(?int $tenantId): array
    {
        static $onbellek = [];
        if (! $tenantId || ! \Illuminate\Support\Facades\Schema::hasTable('cariler')) {
            return [];
        }

        return $onbellek[$tenantId] ??= static::where('tenant_id', $tenantId)->pluck('unvan', 'gln')->all();
    }

    protected function unvan(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(set: fn ($v) => \App\Support\Metin::baslik((string) $v));
    }
}
