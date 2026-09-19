<?php

return [

    'name' => env('APP_NAME', 'DepoWork İTS'),

    /*
     * Sayfa altlığında (footer) gösterilen sürüm numarası. Her anlamlı
     * dağıtımda elle güncellenir (composer.json içindeki paket sürümüyle
     * karıştırılmamalıdır).
     */
    'version' => '0.1.0',

    'company' => 'Depo Setup Yazılım ve Danışmanlık Ltd. Şti.',

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'https://its.depowork.com.tr'),

    'timezone' => env('APP_TIMEZONE', 'Europe/Istanbul'),

    'locale' => env('APP_LOCALE', 'tr'),

    'fallback_locale' => 'en',

    'faker_locale' => 'tr_TR',

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
