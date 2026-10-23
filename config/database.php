<?php

return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'library'),
            'username' => env('DB_USERNAME', 'library'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            // El esquema propio va primero; public queda para las extensiones.
            'search_path' => env('DB_SCHEMA', 'library').',public',
            'sslmode' => 'prefer',
        ],
    ],

    'migrations' => [
        // El historial de Laravel va en public; library conserva solo las seis
        // tablas del modelo de negocio.
        'table' => 'public.migrations',
    ],
];
