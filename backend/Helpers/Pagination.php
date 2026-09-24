<?php

declare(strict_types=1);

namespace App\Helpers;

class Pagination
{
    public static function build(array $items, int $total, int $page, int $perPage): array
    {
        $lastPage = $perPage > 0 ? (int)ceil($total / $perPage) : 0;
        $page = max(1, min($page, max(1, $lastPage)));
        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => $total === 0 ? 0 : min($page * $perPage, $total),
            ],
        ];
    }

    public static function page(?int $page): int
    {
        return $page !== null && $page > 0 ? $page : 1;
    }

    public static function perPage(?int $perPage): int
    {
        $default = (int)app_config('pagination.default_per_page', 10);
        $perPage = $perPage ?? $default;
        return max(1, min($perPage, 100));
    }
}