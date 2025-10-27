<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://solid-space-happiness-v7vwxp5r44wf69qj-5173.app.github.dev',
        'https://solid-space-happiness-v7vwxp5r44wf69qj-8000.app.github.dev',
    ],

    'allowed_origins_patterns' => [
        // Permite todos los subdominios de GitHub Codespaces
        '/^https:\/\/.*\.app\.github\.dev$/',
    ],

    'allowed_headers' => [
        '*',
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
    ],

    'exposed_headers' => [
        'Authorization',
    ],

    'max_age' => 86400, // 24 horas en segundos

    'supports_credentials' => true,
];