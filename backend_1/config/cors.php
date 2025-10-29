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
        // URLs de tu frontend y backend
        'https://solid-space-happiness-v7vwxp5r44wf69qj-5173.app.github.dev',
        'https://solid-space-happiness-v7vwxp5r44wf69qj-8000.app.github.dev',
    ],

    'allowed_origins_patterns' => [
        // 🔥 Permitir cualquier subdominio de GitHub Codespaces
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
        'X-XSRF-TOKEN',
    ],

    'max_age' => 0,

    // 🔥 MUY IMPORTANTE: debe estar en TRUE
    'supports_credentials' => true,
];
