<?php

declare(strict_types=1);

namespace App\Helpers;

class Permissions
{
    private const ALL_PERMISSIONS = [
        'dashboard.view',
        'customers.read', 'customers.create', 'customers.update', 'customers.delete',
        'services.read', 'services.create', 'services.update', 'services.delete',
        'categories.read', 'categories.create', 'categories.update', 'categories.delete',
        'packages.read', 'packages.create', 'packages.update', 'packages.delete',
        'enquiries.read', 'enquiries.update', 'enquiries.convert',
        'quotations.read', 'quotations.create', 'quotations.update', 'quotations.status',
        'bookings.read', 'bookings.create', 'bookings.update', 'bookings.status',
        'events.read', 'events.create', 'events.update', 'events.delete', 'events.status',
        'staff.read', 'staff.create', 'staff.update', 'staff.delete', 'assignments.manage',
        'payments.read', 'payments.create', 'payments.reverse',
        'invoices.read', 'invoices.create', 'invoices.update', 'invoices.delete',
        'reviews.read', 'reviews.moderate', 'reviews.delete',
        'offers.read', 'offers.create', 'offers.update', 'offers.delete',
        'leads.read', 'leads.create', 'leads.update', 'leads.delete', 'followups.manage',
        'reports.read',
        'notifications.read', 'notifications.update',
        'settings.read', 'settings.update',
        'users.read', 'users.create', 'users.update', 'users.delete',
        'roles.read',
        'activity.read',
    ];

    private const MANAGER_DENIED = [
        'customers.delete',
        'invoices.delete',
        'invoices.create',
        'reviews.delete',
        'payments.reverse',
        'settings.*',
        'users.*',
        'roles.*',
        'activity.read',
    ];

    private const STAFF_PERMISSIONS = [
        'dashboard.view',
        'customers.read',
        'bookings.read', 'bookings.update',
        'events.read', 'events.create', 'events.update', 'events.status',
        'staff.read',
        'assignments.manage',
        'followups.manage',
        'reviews.moderate',
        'notifications.read', 'notifications.update',
    ];

    public static function all(): array
    {
        return self::ALL_PERMISSIONS;
    }

    public static function rolePermissions(string $roleSlug): array
    {
        if ($roleSlug === 'admin') {
            return self::ALL_PERMISSIONS;
        }
        if ($roleSlug === 'manager') {
            return array_values(array_filter(
                self::ALL_PERMISSIONS,
                fn(string $permission): bool => !self::isDeniedForManager($permission)
            ));
        }
        if ($roleSlug === 'staff') {
            return self::STAFF_PERMISSIONS;
        }
        return [];
    }

    private static function isDeniedForManager(string $permission): bool
    {
        foreach (self::MANAGER_DENIED as $denied) {
            if (str_ends_with($denied, '.*')) {
                $deniedPrefix = rtrim($denied, '.*');
                if (str_starts_with($permission, $deniedPrefix . '.')) {
                    return true;
                }
            } elseif ($denied === $permission) {
                return true;
            }
        }
        return false;
    }

    public static function allows(string $roleSlug, string $permission): bool
    {
        $granted = self::rolePermissions($roleSlug);
        if (in_array($permission, $granted, true)) {
            return true;
        }
        $lastDot = strrpos($permission, '.');
        if ($lastDot !== false) {
            $prefix = substr($permission, 0, $lastDot);
            if (in_array($prefix . '.*', $granted, true)) {
                return true;
            }
        }
        return false;
    }
}