<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'depot_id',
        'starts_at',
        'ends_at',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function reminders()
    {
        return $this->hasMany(LicenseReminder::class);
    }

    public function daysRemaining(): int
    {
        return now()->diffInDays($this->ends_at, false);
    }
}
