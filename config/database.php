<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'ocr_capyc_vendedores'),

    'connections' => [
        'ocr_capyc_vendedores' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '192.168.1.142'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'ocr_capyc_vendedores'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'Capyc.1234'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],

        'totvs' => [
            'driver' => 'pgsql',
            'host' => env('DADOSPRO_DB_HOST', '192.168.1.141'),
            'port' => env('DADOSPRO_DB_PORT', '5432'),
            'database' => env('DADOSPRO_DB_DATABASE', 'dadospro2310'),
            'username' => env('DADOSPRO_DB_USERNAME', 'postgres'),
            'password' => env('DADOSPRO_DB_PASSWORD', 'Capyc.1234'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
