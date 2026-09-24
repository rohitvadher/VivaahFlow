<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfGuard implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next): void
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            $next();
            return;
        }
        $token = $request->header('X-CSRF-Token');
        if ($token === null || $token === '') {
            $token = $request->input('_token');
        }
        if (!is_string($token) || !Session::verifyToken($token)) {
            Response::forbidden('Your session has expired. Please refresh the page and try again.');
        }
        $next();
    }
}