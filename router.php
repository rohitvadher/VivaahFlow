<?php

declare(strict_types=1);

/**
 * VivaahFlow router for the PHP built-in development server.
 *
 * Apache (.htaccess) is the primary server; this file emulates its rewrites
 * when using the built-in server (which ignores .htaccess):
 *
 *   php -S 127.0.0.1:8100 router.php
 *   (document root = project root; do NOT use -t with this router)
 *
 * Without this router, /assets/* URLs 404 and pages render unstyled with
 * dead JavaScript. With it, assets, uploads, the JSON API and all clean
 * routes (/setup, /services, /account, /manage/...) work.
 */

$uri = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

// 1. Public asset alias: /assets/* -> frontend/assets/* (mirrors .htaccess).
if (str_starts_with($uri, '/assets/')) {
    $file = __DIR__ . '/frontend' . $uri;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ][$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        return true;
    }
    http_response_code(404);
    echo 'Asset not found.';
    return true;
}

// 2. JSON API with PATH_INFO routing.
if ($uri === '/api/v1/index.php' || str_starts_with($uri, '/api/v1/index.php/')) {
    $_SERVER['SCRIPT_NAME'] = '/api/v1/index.php';
    $_SERVER['PATH_INFO'] = substr($uri, strlen('/api/v1/index.php')) ?: '/';
    require __DIR__ . '/api/v1/index.php';
    return true;
}

// 3. Real files (uploads, favicon, etc.) are served natively.
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

// 4. Everything else goes through the front dispatcher (clean routes).
require __DIR__ . '/index.php';
return true;
