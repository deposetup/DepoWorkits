<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItsNotification extends Model
{
    use HasFactory;

    // Bildirim türleri (menü yapısıyla eşleşir)
    public const TYPE_ALIM = 'alim';
    public const TYPE_SATIS = 'satis';
    public const TYPE_DEVIR = 'devir';
    public const TYPE_ECZANE_SATIS = 'eczane_satis';
    public const TYPE_IHRACAT = 'ihracat';
    public const TYPE_URETIM = 'uretim';
    public const TYPE_DEAKTIVASYON = 'deaktivasyon';
    public const TYPE_IPTAL_DEVIR = 'iptal_devir';
    public const TYPE_IPTAL_ECZANE_SATIS = 'iptal_eczane_satis';
    public const TYPE_IPTAL_IADE = 'iptal_iade';
    public const TYPE_IPTAL_IHRACAT = 'iptal_ihracat';
    public const TYPE_IPTAL_SATIS = 'iptal_satis';
    public const TYPE_IPTAL_DEAKTIVASYON = 'iptal_deaktivasyon';

    protected $fillable = [
        'depot_id',
        'type',
        'payload',
        'its_response',
        'status',
        'its_reference',
        'attempts',
        'error_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'its_response' => 'array',
        ];
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ALIM => 'Mal Alım',
            self::TYPE_SATIS => 'Satış',
            self::TYPE_DEVIR => 'Mal Devir',
            self::TYPE_ECZANE_SATIS => 'Eczane Satış',
            self::TYPE_IHRACAT => 'İhracat',
            self::TYPE_URETIM => 'Üretim',
            self::TYPE_DEAKTIVASYON => 'Deaktivasyon',
            self::TYPE_IPTAL_DEVIR => 'Mal Devir İptali',
            self::TYPE_IPTAL_ECZANE_SATIS => 'Eczane Satış İptali',
            self::TYPE_IPTAL_IADE => 'Mal İade',
            self::TYPE_IPTAL_IHRACAT => 'İhracat İptali',
            self::TYPE_IPTAL_SATIS => 'Satış İptali',
            self::TYPE_IPTAL_DEAKTIVASYON => 'Deaktivasyon İptali',
            default => $this->type,
        };
    }
}
