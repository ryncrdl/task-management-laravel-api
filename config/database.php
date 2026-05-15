<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| Supports both individual DB_* variables (local) and a single DATABASE_URL
| (Render.com / Heroku style). DATABASE_URL takes priority when present.
*/

$dbUrl = env('DATABASE_URL');
$pgsql = [];

if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    $pgsql = [
        'driver'   => 'pgsql',
        'host'     => $parsed['host'],
        'port'     => $parsed['port'] ?? 5432,
        'database' => ltrim($parsed['path'], '/'),
        'username' => $parsed['user'],
        'password' => $parsed['pass'],
        'sslmode'  => 'require',
    ];
} else {
    $pgsql = [
        'driver'   => 'pgsql',
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'task_management'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'sslmode'  => env('DB_SSLMODE', 'prefer'),
    ];
}

return [

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        'sqlite' => [
            'driver'                  => 'sqlite',
            'url'                     => env('DB_URL'),
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'pgsql' => array_merge($pgsql, [
            'charset'   => 'utf8',
            'prefix'    => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'schema'    => 'public',
        ]),

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'laravel'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],

    ],

    'migrations' => [
        'table'  => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],

];
