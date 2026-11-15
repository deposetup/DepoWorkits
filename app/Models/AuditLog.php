<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['detay' => 'array'];
    }

    public static function kaydet(string $islem, array $detay = []): void
    {
        $user = auth()->user();
        static::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'islem' => $islem,
            'detay' => $detay,
            'ip' => request()->ip(),
        ]);
    }
}
