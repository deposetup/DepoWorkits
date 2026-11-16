<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtsPaketi extends Model
{
    protected $table = 'pts_paketleri';
    protected $guarded = [];
    protected $hidden = ['xml'];

    protected function casts(): array
    {
        return ['md5_uyumlu' => 'boolean', 'ilerleme' => 'array'];
    }
}
