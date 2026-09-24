<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $config = app_config('session');
        $secure = (bool)$config['secure'];
        if (!$secure && function_exists('is_https_request') && is_https_request()) {
            // Never send session cookies over cleartext when the request is HTTPS,
            // even if the operator left SESSION_SECURE=false on localhost.
            $secure = true;
        }
        session_name((string)$config['name']);
        session_set_cookie_params([
            'lifetime' => (int)$config['lifetime'],
            'path' => '/',
            'httponly' => true,
            'secure' => $secure,
            'samesite' => (string)$config['same_site'],
        ]);
        session_start();
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        unset($_SESSION[$key]);
        return $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }
    }

    public static function csrfToken(): string
    {
        if (!self::has('_csrf') || !is_string(self::get('_csrf'))) {
            self::set('_csrf', bin2hex(random_bytes(16)));
        }
        return (string)self::get('_csrf');
    }

    public static function verifyToken(string $token): bool
    {
        $expected = self::csrfToken();
        return $token !== '' && is_string($token) && hash_equals($expected, $token);
    }
}