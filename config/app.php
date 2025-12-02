<?php

return [
    'name'            => env('APP_NAME', 'Laravel'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'http://localhost:8000'),
    'asset_url'       => env('ASSET_URL'),
    'timezone'        => 'America/Argentina/Buenos_Aires',
    'locale'          => 'es',
    'fallback_locale' => 'es',
    'faker_locale'    => 'es_AR',
    'cipher'          => 'AES-256-CBC',
    'key'             => env('APP_KEY'),
    'frontend_url'    => env('FRONTEND_URL', env('APP_URL')),

    'previous_keys'   => [
         ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance'     => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
