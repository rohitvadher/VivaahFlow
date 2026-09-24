<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/bootstrap.php';

use App\Core\Session;
use App\Helpers\Permissions;

if (!function_exists('admin_auth')) {
    function admin_auth(): void
    {
        if (!Session::get('user_id')) {
            redirect(route_path('manage.login'));
        }
        if ((string)Session::get('user_role') === 'customer') {
            redirect(route_path('account.dashboard'));
        }
    }

    function admin_role(): string
    {
        return (string)Session::get('user_role', '');
    }

    function admin_permissions(): array
    {
        static $permissions = null;
        if ($permissions === null) {
            $permissions = Permissions::rolePermissions(admin_role());
        }
        return $permissions;
    }

    function admin_can(string $permission): bool
    {
        return Permissions::allows(admin_role(), $permission);
    }

    function admin_user(): array
    {
        return [
            'id' => (int)Session::get('user_id', 0),
            'name' => (string)Session::get('user_name', 'User'),
            'email' => (string)Session::get('user_email', ''),
            'role' => admin_role(),
        ];
    }

    function initials_of(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn(string $part): bool => $part !== ''));
        if ($parts === []) {
            return '?';
        }
        if (count($parts) === 1) {
            return strtoupper(mb_substr($parts[0], 0, 2));
        }
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
    }

    function role_label(string $role): string
    {
        $labels = [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'customer' => 'Customer',
        ];
        return $labels[$role] ?? ucfirst($role);
    }
}

admin_auth();