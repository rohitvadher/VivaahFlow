<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class EnsurePanelUser implements MiddlewareInterface
{
    public function __construct(private AuthService $authService)
    {
    }

    public function handle(Request $request, callable $next): void
    {
        $user = $this->authService->user();
        if ($user === null) {
            Response::unauthorized();
        }
        $role = (string)($user['role_slug'] ?? '');
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) {
            Response::forbidden('This area is restricted to staff accounts.');
        }
        $next();
    }
}