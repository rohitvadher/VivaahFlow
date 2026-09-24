<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class EnsureCustomer implements MiddlewareInterface
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
        if (($user['role_slug'] ?? '') !== 'customer' || empty($user['customer']['id'])) {
            Response::forbidden('This area is restricted to customer accounts.');
        }
        $next();
    }
}