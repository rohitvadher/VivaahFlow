<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $files;
    private ?array $user = null;
    private array $attributes = [];
    private static ?Request $instance = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = $this->resolvePath();
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->body = $this->parseBody();
    }

    public static function current(): Request
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function resolvePath(): string
    {
        $path = '';
        if (isset($_SERVER['PATH_INFO']) && is_string($_SERVER['PATH_INFO'])) {
            $path = $_SERVER['PATH_INFO'];
        } elseif (isset($_GET['route']) && is_string($_GET['route'])) {
            $path = '/' . ltrim($_GET['route'], '/');
        }
        if ($path === '' || $path === '/') {
            return '/';
        }
        $parsed = parse_url($path, PHP_URL_PATH);
        if (is_string($parsed)) {
            $path = $parsed;
        }
        $base = (string)app_config('api_base', '/api/v1');
        if ($base !== '/' && stripos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (is_string($contentType) && str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                return [];
            }
            return [];
        }
        return $_POST;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->body, $this->query);
    }

    public function input(string $key, $default = null)
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }
        return $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    public function file(string $key): ?array
    {
        if (isset($this->files[$key]) && is_array($this->files[$key])) {
            $error = $this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($error !== UPLOAD_ERR_NO_FILE && $error !== UPLOAD_ERR_OK && $error !== 0) {
                return null;
            }
            if ($error === UPLOAD_ERR_OK || ($this->files[$key]['size'] ?? 0) > 0) {
                return $this->files[$key];
            }
        }
        return null;
    }

    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function userId(): ?int
    {
        return isset($this->user['id']) ? (int)$this->user['id'] : null;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) && is_string($_SERVER[$key]) ? $_SERVER[$key] : null;
    }
}