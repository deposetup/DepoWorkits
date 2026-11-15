<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestekTalebi extends Model
{
    protected $table = 'destek_talepleri';
    protected $guarded = [];

    public const KONULAR = [
        'its_sifre' => 'İTS şifremi değiştirmek istiyorum',
        'its_kullanici' => 'İTS kullanıcı adı / GLN bilgim değişti',
        'baglanti' => 'Bağlantı sınaması başarısız oluyor',
        'diger' => 'Diğer',
    ];
}
