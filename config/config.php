<?php

declare(strict_types=1);

/**
 * VivaahFlow configuration.
 *
 * Defaults work out of the box for XAMPP. Every value below can be
 * overridden with an environment variable (see .env.example) without
 * editing this file:
 *
 *   DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
 *   APP_NAME / APP_DEBUG / APP_TIMEZONE / APP_CURRENCY / APP_CURRENCY_SYMBOL
 *   SESSION_NAME / SESSION_SECURE
 */

if (!function_exists('vivaah_env')) {
    function vivaah_env(string $key, $default = null)
    {
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

return [

    'app_name' => (string)vivaah_env('APP_NAME', 'VivaahFlow'),

    'app_version' => '1.0.0',

    'schema_version' => 1,

    'timezone' => (string)vivaah_env('APP_TIMEZONE', 'Asia/Kolkata'),

    'debug' => in_array(strtolower((string)vivaah_env('APP_DEBUG', 'false')), ['1', 'true', 'yes', 'on'], true),

    'http' => [
        'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',
        'scheme' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http',
        // Set to true only behind a trusted reverse proxy that sets X-Forwarded-Proto.
        'trust_proxy' => false,
    ],

    'database' => [
        'host' => (string)vivaah_env('DB_HOST', '127.0.0.1'),
        'port' => (int)vivaah_env('DB_PORT', 3306),
        'name' => (string)vivaah_env('DB_NAME', 'vivaahflow'),
        'user' => (string)vivaah_env('DB_USER', 'root'),
        'pass' => (string)vivaah_env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name' => (string)vivaah_env('SESSION_NAME', 'vivaahflow_sess'),
        'lifetime' => 7200,
        'same_site' => 'Lax',
        // SESSION_SECURE=true on HTTPS production; auto-enables on HTTPS requests.
        'secure' => in_array(strtolower((string)vivaah_env('SESSION_SECURE', 'false')), ['1', 'true', 'yes', 'on'], true),
    ],

    'security' => [
        'login_max_attempts' => 5,
        'login_lock_minutes' => 15,
        'upload_max_bytes' => 3145728,
        'allowed_image_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'admin_email' => 'admin@vivaahflow.test',
    ],

    'pagination' => [
        'default_per_page' => 10,
    ],

    'paths' => [
        'uploads' => '/uploads',
        'logs' => '/storage/logs',
    ],

    'currency' => [
        'symbol' => (string)vivaah_env('APP_CURRENCY_SYMBOL', '₹'),
        'code' => (string)vivaah_env('APP_CURRENCY', 'INR'),
        'decimals' => 2,
    ],

    'api_base' => '/api/v1',

    'routes' => [
        'customer_base' => '',
        'portal_base' => '/account',
        'admin_base' => '/manage',
    ],
];
