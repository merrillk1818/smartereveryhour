<?php

return [

    'pushover' => [
        'app_token' => env('PUSHOVER_APP_TOKEN'),
    ],

    'vonage' => [
        'api_key'    => env('VONAGE_API_KEY'),
        'api_secret' => env('VONAGE_API_SECRET'),
        'from'       => env('VONAGE_FROM_NUMBER', '17547522194'),
    ],

    'turnstile' => [
        'site_key'   => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'vapid' => [
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject'     => env('VAPID_SUBJECT', 'https://smartereveryhour.com'),
    ],

];
