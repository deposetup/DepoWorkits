<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItsCredential extends Model
{
    protected $guarded = [];

    protected $hidden = ['kullanici_adi', 'sifre'];

    protected function casts(): array
    {
        // Laravel APP_KEY ile AES-256 şifreleme; ekranda asla düz gösterilmez.
        return ['kullanici_adi' => 'encrypted', 'sifre' => 'encrypted'];
    }
}
