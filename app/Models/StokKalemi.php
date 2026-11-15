<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokKalemi extends Model
{
    protected $table = 'stok_kalemleri';
    protected $guarded = [];

    public const RED_ODASI = 'Red Odası';

    /** Migration henüz çalışmadıysa lokasyon özellikleri sessizce devre dışı kalır. */
    public static function lokasyonVar(): bool
    {
        static $var;

        return $var ??= \Illuminate\Support\Facades\Schema::hasColumn('stok_kalemleri', 'lokasyon');
    }

    /** SKT'si geçmiş stoktaki ürünleri otomatik "Red Odası" lokasyonuna alır. */
    public static function redOdasinaAl(?int $tenantId): void
    {
        if (! $tenantId || ! static::lokasyonVar()) {
            return;
        }
        static::where('tenant_id', $tenantId)->where('durum', 'stokta')->whereNotNull('skt')
            ->where('skt', '<', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('lokasyon')->orWhere('lokasyon', '!=', self::RED_ODASI))
            ->update(['lokasyon' => self::RED_ODASI]);
    }

    /** Müşterinin kullandığı lokasyon adları (öneri listesi için). */
    public static function lokasyonlar(?int $tenantId): array
    {
        if (! $tenantId || ! static::lokasyonVar()) {
            return [];
        }

        return static::where('tenant_id', $tenantId)->whereNotNull('lokasyon')->distinct()->orderBy('lokasyon')->pluck('lokasyon')->all();
    }

    protected function casts(): array
    {
        return ['skt' => 'date', 'son_islem_at' => 'datetime'];
    }
}
