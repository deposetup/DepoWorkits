<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtsGonderim extends Model
{
    protected $table = 'pts_gonderimleri';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['kalemler' => 'array', 'gonderildi_at' => 'datetime'];
    }
}
