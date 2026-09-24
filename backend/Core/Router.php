<?php

declare(strict_types=1);

namespace App\Core;

class Route
{
    public string $method;
    public string $pattern;
    public array $segments;
    public string $action;
    public array $middleware;

    public function __construct(string $method, string $pattern, string $action, array $middleware = [])
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->action = $action;
        $this->middleware = $middleware;
        $this->segments = self::normalize($pattern);
    }

    public static function normalize(string $path): array
    {
        $segments = explode('/', $path);
        return array_values(array_filter($segments, fn(string $segment): bool => $segment !== ''));
    }
}

class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];

    public function addGlobal(array $middleware): void
    {
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
    }

    public function get(string $pattern, string $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, string $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $middleware);
    }

    public function put(string $pattern, string $action, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $action, $middleware);
    }

    public function patch(string $pattern, string $action, array $middleware = []): void
    {
        $this->add('PATCH', $pattern, $action, $middleware);
    }

    public function delete(string $pattern, string $action, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $action, $middleware);
    }

    private function add(string $method, string $pattern, string $action, array $middleware): void
    {
        $pattern = '/' . trim($pattern, '/');
        $this->routes[] = new Route($method, $pattern, $action, $middleware);
    }

    public function dispatch(Request $request, Container $container): void
    {
        $pathSegments = Route::normalize($request->path());
        foreach ($this->routes as $route) {
            if ($route->method !== $request->method()) {
                continue;
            }
            $params = $this->match($route->segments, $pathSegments);
            if ($params !== null) {
                foreach ($params as $key => $value) {
                    $request->setAttribute($key, $value);
                }
                $middleware = array_merge($this->globalMiddleware, $route->middleware);
                $this->runMiddleware($middleware, $request, $container, function () use ($route, $request, $container): void {
                    $this->handle($route, $request, $container);
                });
                return;
            }
        }
        Response::error('Route not found.', 404);
    }

    private function match(array $pattern, array $path): ?array
    {
        if (count($pattern) !== count($path)) {
            return null;
        }
        $params = [];
        foreach ($pattern as $index => $segment) {
            if (preg_match('/^\{(\w+)\}$/', $segment, $matches) === 1) {
                $params[$matches[1]] = urldecode($path[$index]);
            } elseif ($segment !== $path[$index]) {
                return null;
            }
        }
        return $params;
    }

    private function runMiddleware(array $middleware, Request $request, Container $container, callable $next): void
    {
        if ($middleware === []) {
            $next();
            return;
        }
        $entry = array_shift($middleware);
        $instance = is_string($entry) ? $container->make($entry) : $entry;
        if ($instance instanceof MiddlewareInterface) {
            $instance->handle($request, function () use ($middleware, $request, $container, $next): void {
                $this->runMiddleware($middleware, $request, $container, $next);
            });
            return;
        }
        if (is_callable($instance)) {
            $instance($request, function () use ($middleware, $request, $container, $next): void {
                $this->runMiddleware($middleware, $request, $container, $next);
            });
            return;
        }
        throw new \RuntimeException('Invalid middleware definition: ' . (is_string($entry) ? $entry : gettype($entry)));
    }

    private function handle(Route $route, Request $request, Container $container): void
    {
        $parts = explode('@', $route->action);
        $controllerClass = 'App\\Controllers\\' . $parts[0];
        $method = $parts[1] ?? 'index';
        $controller = $container->make($controllerClass);
        try {
            $controller->{$method}($request);
        } catch (BusinessException $e) {
            $status = $e->getCode() >= 400 ? $e->getCode() : 422;
            Response::error($e->getMessage(), $status, $e->getErrors() !== [] ? $e->getErrors() : null);
        }
    }
}