<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', 'cookie'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => env('SESSION_ENCRYPT', true),
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => null,
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', Str::slug((string) env('APP_NAME', 'laravel')).'-session'),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    'partitioned' => false,
    'serialization' => 'json',
];
