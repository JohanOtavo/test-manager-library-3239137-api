<?php

return [
    // Las reglas con número viven aquí y en ningún otro sitio (RN-2).
    'loan_days' => 14,
    'max_open_books_per_member' => 3,
    'max_items_per_loan' => 3,
    'page_size_max' => 50,
    'page_size_default' => 20,

    // Token de 8 horas (CA-01.1).
    'token_ttl_seconds' => 28800,
    'jwt_secret' => env('JWT_SECRET'),

    // Portadas: JPG y PNG, 2 MB (CA-03.4).
    'cover_max_bytes' => 2097152,
    'cover_mime_types' => ['image/jpeg', 'image/png'],

    // Desvío de reloj para el sembrador de demostración. Solo en local:
    // sin esta variable, la cabecera X-Library-Clock-Offset-Days se ignora.
    'demo_clock' => env('DEMO_CLOCK', false) && env('APP_ENV') === 'local',

    // Administrador inicial, desde el entorno y nunca desde la base (data-model §9).
    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
