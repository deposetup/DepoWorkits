<?php

return [

    'its' => [
        'base_url' => env('ITS_API_BASE_URL'),
        'timeout' => env('ITS_API_TIMEOUT', 30),
        'license_reminder_days' => env('LICENSE_REMINDER_DAYS', '30,14,7,1'),
    ],

];
