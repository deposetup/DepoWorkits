<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hareket extends Model
{
    protected $table = 'hareketler';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['skt' => 'date', 'basarili' => 'boolean'];
    }
}
