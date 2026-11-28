<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// paydas:izle, Plesk zamanlanmış görevleriyle hafta içi 12:00 ve 17:05'te çalıştırılır (iki ayrı görev) (Laravel scheduler kullanılmıyor).
