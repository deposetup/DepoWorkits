<?php

return [

    'its' => [
        'base_url' => env('ITS_API_BASE_URL'),
        'timeout' => env('ITS_API_TIMEOUT', 30),
        // Stok sorgulama servis adresi — resmi adres doğrulanınca .env'e yazılacak.
        'stock_endpoint' => env('ITS_STOCK_ENDPOINT'),
        'license_reminder_days' => env('LICENSE_REMINDER_DAYS', '30,14,7,1'),
    ],

];
