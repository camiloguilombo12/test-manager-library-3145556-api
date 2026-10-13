<?php

/*
 * Configuración propia de Manager Library. Las variables solo se leen aquí, nunca con env() en el código.
 */
return [
    'jwt_secret' => env('JWT_SECRET'),
    'jwt_ttl_seconds' => (int) env('JWT_TTL_SECONDS', 28800),

    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),

    'covers_dir' => env('COVERS_DIR', base_path('var/covers')),
    'cors_origin' => env('CORS_ORIGIN', 'http://localhost:5173'),

    // Solo para sembrar préstamos vencidos de la demo (spec-review.md E-02)
    'clock_offset_days' => (int) env('CLOCK_OFFSET_DAYS', 0),
];
