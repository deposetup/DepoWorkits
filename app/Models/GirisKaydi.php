<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GirisKaydi extends Model
{
    protected $table = 'giris_kayitlari';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['basarili' => 'boolean', 'created_at' => 'datetime'];
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Giriş denemesini kaydeder. Tablo yoksa ya da yazılamazsa girişi asla engellemez. */
    public static function kaydet(?User $user, string $kimlik, string $yontem, bool $basarili): void
    {
        try {
            static::create([
                'user_id' => $user?->id, 'tenant_id' => $user?->tenant_id,
                'kimlik' => mb_substr($kimlik, 0, 255), 'yontem' => $yontem, 'basarili' => $basarili,
                'ip' => request()->ip(), 'tarayici' => mb_substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Giriş kaydı yazılamadı: '.$e->getMessage());
        }
    }
}
