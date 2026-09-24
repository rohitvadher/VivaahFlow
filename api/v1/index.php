<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Core\Routes;
use App\Middleware\CsrfGuard;

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$request = Request::current();

$container = new Container([
    Request::class => $request,
]);

$router = new Router();
$router->addGlobal([CsrfGuard::class]);
Routes::register($router);
$router->dispatch($request, $container);