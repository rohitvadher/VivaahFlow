<?php

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('BACKEND_PATH')) {
    define('BACKEND_PATH', ROOT_PATH . '/backend');
}
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', ROOT_PATH . '/storage');
}

require BACKEND_PATH . '/Helpers/Functions.php';

date_default_timezone_set((string)app_config('timezone', 'UTC'));

if (app_config('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

ini_set('log_errors', '1');

$writableDirs = [
    STORAGE_PATH . '/logs',
    ROOT_PATH . '/uploads',
    ROOT_PATH . '/uploads/customers',
    ROOT_PATH . '/uploads/services',
    ROOT_PATH . '/uploads/packages',
    ROOT_PATH . '/uploads/settings',
    ROOT_PATH . '/uploads/temp',
];
foreach ($writableDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

set_exception_handler(function (Throwable $e): void {
    $previous = '';
    $chain = $e->getPrevious();
    if ($chain instanceof Throwable) {
        $previous = ' Previous: ' . $chain->getMessage();
    }
    error_log('[EXCEPTION] ' . get_class($e) . ': ' . $e->getMessage() . $previous . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        $message = app_config('debug', false) ? $e->getMessage() : 'An unexpected error occurred. Check storage/logs/php-error.log.';
        fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
        exit(1);
    }
    $isApi = isset($_SERVER['SCRIPT_NAME']) && str_contains((string)$_SERVER['SCRIPT_NAME'], '/api/');
    if ($isApi && class_exists('App\\Core\\Response')) {
        $message = strtolower($e->getMessage());
        if (
            str_contains($message, 'unknown database')
            || str_contains($message, 'no such database')
            || str_contains($message, 'base table or view not found')
            || str_contains($message, "doesn't exist")
        ) {
            \App\Core\Response::error(
                'Setup required. Open /setup in your browser to complete the VivaahFlow installation.',
                503,
                ['setup_required' => true]
            );
        }
        if (
            str_contains($message, 'connection refused')
            || str_contains($message, 'connection attempt failed')
            || str_contains($message, 'access denied for user')
            || $e instanceof PDOException
        ) {
            $safe = app_config('debug', false)
                ? 'Database error: ' . $e->getMessage()
                : 'The database is currently unavailable. Please try again shortly or contact the administrator.';
            \App\Core\Response::error($safe, 503);
        }
        $safe = app_config('debug', false)
            ? 'Error: ' . $e->getMessage()
            : 'An unexpected error occurred.';
        \App\Core\Response::error($safe, 500);
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'An unexpected error occurred.';
});

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = BACKEND_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

if (PHP_SAPI !== 'cli') {
    \App\Core\Session::start();
    // CDN-compatible hardening headers (no CSP: Tailwind Play + inline
    // config blocks require 'unsafe-inline'; a restrictive CSP would break
    // the current frontend). Sent here so built-in server gets them too.
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
}
