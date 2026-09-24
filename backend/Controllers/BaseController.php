<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Pagination;
use App\Helpers\Permissions;

abstract class BaseController
{
    protected function user(): ?array
    {
        return Request::current()->user();
    }

    protected function userId(): ?int
    {
        return Request::current()->userId();
    }

    protected function requirePermission(string $permission): array
    {
        $user = $this->user();
        if ($user === null) {
            throw new \App\Core\BusinessException('Please sign in to continue.', 401);
        }
        $role = (string)($user['role_slug'] ?? '');
        if (!Permissions::allows($role, $permission)) {
            throw new \App\Core\BusinessException('You do not have permission to perform this action.', 403);
        }
        return $user;
    }

    protected function page(): int
    {
        return Pagination::page((int)Request::current()->input('page', 1));
    }

    protected function perPage(): int
    {
        $value = Request::current()->input('per_page');
        return Pagination::perPage($value !== null ? (int)$value : app_config('pagination.default_per_page', 10));
    }

    protected function search(): string
    {
        return trim((string)Request::current()->input('search', ''));
    }

    protected function statusFilter(string $default = 'all'): string
    {
        $value = trim((string)Request::current()->input('status', $default));
        return $value === '' ? $default : $value;
    }

    protected function routeId(Request $request): int
    {
        return (int)$request->attribute('id', 0);
    }

    protected function success($data = null, string $message = 'OK'): void
    {
        \App\Core\Response::success($data, $message);
    }

    protected function created($data = null, string $message = 'Created successfully.'): void
    {
        \App\Core\Response::created($data, $message);
    }
}