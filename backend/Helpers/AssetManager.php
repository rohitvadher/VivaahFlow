<?php

declare(strict_types=1);

namespace App\Helpers;

class AssetManager
{
    private const FONTS_CSS = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap';

    private const LIBRARIES = [
        'flowbite' => [
            'css' => 'https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.5.1/flowbite.min.css',
            'js' => 'https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.5.1/flowbite.min.js',
        ],
        'lucide' => [
            'js' => 'https://unpkg.com/lucide@0.454.0/dist/umd/lucide.min.js',
        ],
        'apexcharts' => [
            'js' => 'https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.min.js',
        ],
        'flatpickr' => [
            'css' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            'js' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js',
        ],
        'swiper' => [
            'css' => 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            'js' => 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        ],
    ];

    private const SHARED_JS = [
        'js/app.config.js',
        'js/app.api.js',
        'js/core/loader.js',
        'js/app.alerts.js',
        'js/app.ui.js',
        'js/app.icons.js',
        'js/app.tables.js',
        'js/app.workflow.js',
        'js/app.upload.js',
    ];

    private const CORE_CSS = [
        'css/core.css',
        'css/components.css',
        'css/layout.css',
        'css/utilities.css',
    ];

    public static function favicon(): string
    {
        return '<link rel="icon" type="image/png" href="' . asset_url('images/brand/logo.png') . '">'
            . '<link rel="alternate icon" type="image/x-icon" href="' . asset_url('icons/favicon.ico') . '">'
            . '<link rel="apple-touch-icon" href="' . asset_url('icons/apple-touch-icon.png') . '">';
    }

    public static function head(array $extraCss = []): string
    {
        $output = '<meta name="csrf-token" content="' . e(\App\Core\Session::csrfToken()) . '">';
        $output .= '<link rel="preconnect" href="https://fonts.googleapis.com">';
        $output .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        $output .= '<link rel="stylesheet" href="' . self::FONTS_CSS . '">';
        $output .= '<script src="https://cdn.tailwindcss.com"></script>';
        $output .= '<link rel="stylesheet" href="' . self::LIBRARIES['flowbite']['css'] . '">';
        foreach (self::CORE_CSS as $css) {
            $output .= '<link rel="stylesheet" href="' . asset_url($css) . '">';
        }
        foreach (array_unique($extraCss) as $css) {
            if (str_starts_with($css, 'http')) {
                $output .= '<link rel="stylesheet" href="' . $css . '">';
            } else {
                $output .= '<link rel="stylesheet" href="' . asset_url($css) . '">';
            }
        }
        return $output;
    }

    public static function cssLinks(array $libraries = []): string
    {
        $output = '';
        foreach (array_unique($libraries) as $library) {
            // Flowbite CSS is already emitted by head(); skip to avoid duplicates.
            if ($library === 'flowbite') {
                continue;
            }
            $css = self::LIBRARIES[$library]['css'] ?? null;
            if ($css !== null) {
                $output .= '<link rel="stylesheet" href="' . $css . '">';
            }
        }
        return $output;
    }

    private static function resolveAsset(string $path): string
    {
        if (str_starts_with($path, 'js/pages/public/')) {
            return 'js/customer/' . substr($path, strlen('js/pages/public/'));
        }
        if (str_starts_with($path, 'js/pages/portal/')) {
            return 'js/portal/' . substr($path, strlen('js/pages/portal/'));
        }
        if (str_starts_with($path, 'js/pages/')) {
            return 'js/admin/' . substr($path, strlen('js/pages/'));
        }
        return $path;
    }

    public static function scripts(string $pageScript, array $libraries = [], array $areaScripts = []): string
    {
        $output = '';
        $output .= '<script>window.APP = ' . self::appConfigJson() . ';</script>';
        foreach (self::SHARED_JS as $script) {
            $output .= '<script src="' . asset_url(self::resolveAsset($script)) . '"></script>';
        }
        foreach (array_unique($areaScripts) as $script) {
            $output .= '<script src="' . asset_url(self::resolveAsset($script)) . '"></script>';
        }
        $requested = array_unique($libraries);
        foreach ($requested as $library) {
            // flowbite/lucide are appended unconditionally below; skipping them
            // here avoids emitting the same CDN script tag twice.
            if ($library === 'flowbite' || $library === 'lucide') {
                continue;
            }
            $jsUrl = self::LIBRARIES[$library]['js'] ?? null;
            if ($jsUrl === null && $library === 'tailwind') {
                $jsUrl = 'https://cdn.tailwindcss.com';
            }
            if ($jsUrl !== null) {
                $output .= '<script src="' . $jsUrl . '"></script>';
            }
        }
        $output .= '<script src="' . self::LIBRARIES['flowbite']['js'] . '"></script>';
        $output .= '<script src="' . self::LIBRARIES['lucide']['js'] . '"></script>';
        if ($pageScript !== '') {
            $output .= '<script src="' . asset_url(self::resolveAsset($pageScript)) . '"></script>';
        }
        return $output;
    }

    public static function appConfigJson(): string
    {
        $csrf = \App\Core\Session::csrfToken();
        $config = [
            'baseUrl' => base_url(''),
            'apiBase' => base_url((string)app_config('api_base', '/api/v1')),
            'apiMethod' => 'pathinfo',
            'csrfToken' => $csrf,
            'appName' => (string)app_config('app_name', 'VivaahFlow'),
            'currencySymbol' => (string)app_config('currency.symbol', '₹'),
            'routes' => FrontendRoutes::map(),
            'routeParams' => $GLOBALS['routeParams'] ?? [],
            'version' => (string)app_config('app_version', '1.0.0'),
        ];
        return json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}