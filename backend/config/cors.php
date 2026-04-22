<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(
        ',',
        (string) env('CORS_ALLOWED_ORIGINS', env('APP_URL', ''))
    )))),

    'allowed_origins_patterns' => array_values(array_filter(array_map('trim', explode(
        ',',
        (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', '')
    )))),

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
