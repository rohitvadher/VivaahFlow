<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/bootstrap.php';

use App\Core\Session;

if (!function_exists('site_user')) {
    function site_user(): array
    {
        if (!Session::get('user_id')) {
            return [];
        }
        return [
            'id' => (int)Session::get('user_id', 0),
            'name' => (string)Session::get('user_name', 'Guest'),
            'email' => (string)Session::get('user_email', ''),
            'role' => (string)Session::get('user_role', ''),
        ];
    }

    function site_is_customer(): bool
    {
        $user = site_user();
        return $user !== [] && $user['role'] === 'customer';
    }

    function site_login_url(): string
    {
        return url_for('login');
    }

    function site_portal_url(): string
    {
        return url_for('account.dashboard');
    }

    function site_initials(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: [], fn(string $part): bool => $part !== ''));
        if ($parts === []) {
            return '?';
        }
        if (count($parts) === 1) {
            return strtoupper(mb_substr($parts[0], 0, 2));
        }
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
    }
}