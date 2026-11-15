<?php

return [
    'driver' => env('ITS_DRIVER', 'mock'),
    'base_url' => env('ITS_BASE_URL', 'https://itstest2.saglik.gov.tr'), // canlı: https://its2.saglik.gov.tr
    // false iken ItsRestClient bildirim (yazma) servislerine istek ATMAZ; yalnızca token ve sorgular çalışır.
    'yazma' => env('ITS_YAZMA', false),
    'whatsapp' => env('SUPPORT_WHATSAPP', ''),
    'max_hatali_giris' => 5,
    'kilit_dakika' => 15,
];
