<?php

declare(strict_types=1);

namespace App\Helpers;

class FrontendRoutes
{
    public static function map(): array
    {
        return [
            'home' => '/',
            'services' => '/services',
            'service' => '/services/{slug}',
            'packages' => '/packages',
            'package' => '/packages/{slug}',
            'gallery' => '/gallery',
            'offers' => '/offers',
            'reviews' => '/reviews',
            'contact' => '/contact',
            'login' => '/login',
            'register' => '/register',
            'setup' => '/setup',
            'account.dashboard' => '/account',
            'account.enquiries' => '/account/enquiries',
            'account.quotations' => '/account/quotations',
            'account.quotation' => '/account/quotations/{id}',
            'account.bookings' => '/account/bookings',
            'account.booking' => '/account/bookings/{id}',
            'account.payments' => '/account/payments',
            'account.invoices' => '/account/invoices',
            'account.invoice' => '/account/invoices/{id}',
            'account.reviews' => '/account/reviews',
            'account.profile' => '/account/profile',
            'manage.dashboard' => '/manage',
            'manage.login' => '/manage/login',
            'manage.customers' => '/manage/customers',
            'manage.customer' => '/manage/customers/{id}',
            'manage.services' => '/manage/services',
            'manage.packages' => '/manage/packages',
            'manage.offers' => '/manage/offers',
            'manage.enquiries' => '/manage/enquiries',
            'manage.leads' => '/manage/leads',
            'manage.quotations' => '/manage/quotations',
            'manage.quotation' => '/manage/quotations/{id}',
            'manage.bookings' => '/manage/bookings',
            'manage.booking' => '/manage/bookings/{id}',
            'manage.events' => '/manage/events',
            'manage.staff' => '/manage/staff',
            'manage.payments' => '/manage/payments',
            'manage.invoices' => '/manage/invoices',
            'manage.invoice' => '/manage/invoices/{id}',
            'manage.reviews' => '/manage/reviews',
            'manage.reports' => '/manage/reports',
            'manage.notifications' => '/manage/notifications',
            'manage.settings' => '/manage/settings',
            'manage.users' => '/manage/users',
            'manage.activity' => '/manage/activity',
            'manage.help' => '/manage/help',
            'manage.profile' => '/manage/profile',
            'api.base' => '/api/v1',
        ];
    }

    public static function path(string $route, array $params = []): string
    {
        $map = self::map();
        $path = $map[$route] ?? $route;
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string)$value), $path);
        }
        return $path;
    }
}
