<?php

declare(strict_types=1);

if (!function_exists('app_config')) {
    function app_config(?string $key = null, $default = null)
    {
        static $config = null;
        if ($config === null) {
            $config = require ROOT_PATH . '/config/config.php';
        }
        if ($key === null) {
            return $config;
        }
        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('env_value')) {
    /**
     * Read an environment variable with a fallback.
     * Checks $_SERVER, then $_ENV, then getenv().
     */
    function env_value(string $key, $default = null)
    {
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

if (!function_exists('is_https_request')) {
    function is_https_request(): bool
    {
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off')) {
            return true;
        }
        // Only trust proxy headers when explicitly enabled via config.
        if (app_config('http.trust_proxy', false) && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('sanitize_host')) {
    /**
     * Strip anything that is not a valid hostname / IP / port.
     * Mitigates host-header injection in generated URLs.
     */
    function sanitize_host(string $host): string
    {
        $host = trim(strtolower($host));
        // Allow host or host:port only.
        if (preg_match('/^([a-z0-9.-]+|\[[a-f0-9:]+\])(:\d{1,5})?$/', $host) === 1) {
            return $host;
        }
        return 'localhost';
    }
}
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        static $base = null;
        if ($base === null) {
            $scheme = (string)app_config('http.scheme', 'http');
            $host = sanitize_host((string)app_config('http.host', 'localhost'));
            $root = str_replace('\\', '/', (string)realpath(ROOT_PATH));
            $documentRoot = str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
            $relative = '';
            if ($documentRoot !== '' && $documentRoot !== '/' && str_starts_with($root, $documentRoot)) {
                $relative = substr($root, strlen($documentRoot));
            }
            $encoded = implode('/', array_map('rawurlencode', array_filter(explode('/', $relative))));
            $base = $scheme . '://' . $host . ($encoded !== '' ? '/' . ltrim($encoded, '/') : '');
        }
        $path = ltrim($path, '/');
        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('root_url')) {
    function root_url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $url = base_url('assets/' . ltrim($path, '/'));
        // Cache-busting so deploys never serve stale CSS/JS.
        $absolute = rtrim(str_replace('\\', '/', (string)realpath(ROOT_PATH)), '/')
            . '/frontend/assets/' . ltrim($path, '/');
        if (is_file($absolute)) {
            $url .= '?v=' . filemtime($absolute);
        }
        return $url;
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }
        return base_url(ltrim($path, '/'));
    }
}

if (!function_exists('route_path')) {
    function route_path(string $route, array $params = []): string
    {
        return \App\Helpers\FrontendRoutes::path($route, $params);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            header('Location: ' . $path);
            exit;
        }
        header('Location: ' . base_url(ltrim($path, '/')));
        exit;
    }
}

if (!function_exists('url_for')) {
    function url_for(string $route, array $params = []): string
    {
        return base_url(\App\Helpers\FrontendRoutes::path($route, $params));
    }
}

if (!function_exists('loader_skeleton')) {
    function loader_skeleton(int $rows = 3, ?int $height = null, bool $full = false): string
    {
        $style = $height === null ? '' : ' style="height:' . $height . 'px"';
        $bars = '';
        for ($i = 0; $i < max(1, $rows); $i++) {
            $bars .= '<div class="skeleton skeleton-row"' . $style . '></div>';
        }
        return '<div class="loader-skeleton' . ($full ? ' is-full' : '') . '">' . $bars . '</div>';
    }
}

if (!function_exists('now_string')) {
    function now_string(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today_string')) {
    function today_string(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('json_prepare')) {
    function json_prepare($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = json_prepare($item);
            }
            return $value;
        }
        return $value;
    }
}

if (!function_exists('setup_required')) {
    function setup_required(): bool
    {
        try {
            return \App\Database\Connection::getStatus() !== 'installed';
        } catch (\Throwable $e) {
            return true;
        }
    }
}

if (!function_exists('setup_url')) {
    function setup_url(): string
    {
        return base_url('setup');
    }
}

if (!function_exists('redirect_to_setup_unless')) {
    /**
     * Central frontend setup guard. Call at the top of each frontend router
     * except the setup router itself. Never throws.
     */
    function redirect_to_setup_unless(bool $isSetupRoute): void
    {
        if ($isSetupRoute || PHP_SAPI === 'cli') {
            return;
        }
        try {
            if (\App\Database\Connection::getStatus() !== 'installed') {
                $current = (string)($_SERVER['REQUEST_URI'] ?? '');
                if (!str_starts_with($current, '/setup') && !str_contains($current, 'setup')) {
                    redirect('setup');
                }
            }
        } catch (\Throwable $e) {
            // If even the status probe fails (server down), let the page render;
            // API bootstrap calls will surface a friendly 503 instead of a fatal.
        }
    }
}

if (!function_exists('normalize_email')) {
    function normalize_email(?string $email): string
    {
        return strtolower(trim((string)$email));
    }
}