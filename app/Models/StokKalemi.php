<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokKalemi extends Model
{
    protected $table = 'stok_kalemleri';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['skt' => 'date', 'son_islem_at' => 'datetime'];
    }
}
