<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseReminder extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'license_id',
        'days_before',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
