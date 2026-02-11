<?php

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods'          => ['*'],

    'allowed_origins'          => [
        'http://localhost:5173',
        'http://127.0.0.1:8000',
        'http://chat-app-laravel.test',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers'          => ['*'],

    'exposed_headers'          => [],

    'max_age'                  => 0,

    'supports_credentials'     => true, // CRITICAL
];
