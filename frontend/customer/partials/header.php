<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Helpers\AssetManager;

$pageTitle = $pageTitle ?? 'Home';
$pageDesc = $pageDesc ?? '';
$activeNav = $activeNav ?? 'home';
$pageScript = $pageScript ?? '';
$extraCss = $extraCss ?? [];
$extraLibraries = $extraLibraries ?? [];
$areaScripts = $areaScripts ?? [];
$currentUser = site_user();
$brandName = (string)app_config('app_name', 'VivaahFlow');

$navItems = [
    'home' => ['label' => 'Home', 'href' => url_for('home')],
    'services' => ['label' => 'Services', 'href' => url_for('services')],
    'packages' => ['label' => 'Packages', 'href' => url_for('packages')],
    'gallery' => ['label' => 'Gallery', 'href' => url_for('gallery')],
    'offers' => ['label' => 'Offers', 'href' => url_for('offers')],
    'reviews' => ['label' => 'Reviews', 'href' => url_for('reviews')],
    'contact' => ['label' => 'Contact', 'href' => url_for('contact')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; <?= e($brandName) ?></title>
    <?php if ($pageDesc !== ''): ?>
        <meta name="description" content="<?= e($pageDesc) ?>">
    <?php endif; ?>
    <?= AssetManager::head(array_merge(['css/site.css'], $extraCss)) ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fdf2fb', 100: '#fbe5f7', 200: '#f5c2eb', 300: '#ec92da',
                            400: '#d95ebf', 500: '#b72c9b', 600: '#8b0a72', 700: '#72095e',
                            800: '#5c0848', 900: '#4a073b'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['"Playfair Display"', 'Georgia', 'serif']
                    }
                }
            }
        };
    </script>
    <?= AssetManager::cssLinks($extraLibraries) ?>
    <?= AssetManager::favicon() ?>
</head>
<body class="site">
<header class="site-header">
    <div class="site-container flex items-center justify-between gap-4">
        <a class="site-brand" href="<?= url_for('home') ?>">
            <img class="site-brand-logo" src="<?= asset_url('images/brand/logo.png') ?>" alt="<?= e($brandName) ?> logo" width="36" height="36">
            <span data-brand-name><?= e($brandName) ?></span>
        </a>
        <button type="button" class="icon-btn lg:hidden" data-site-nav-toggle aria-label="Toggle menu">
            <i data-lucide="menu"></i>
        </button>
        <nav class="site-nav" data-site-nav>
            <?php foreach ($navItems as $key => $item): ?>
                <a class="site-nav-link<?= $activeNav === $key ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="site-actions">
            <?php if (site_is_customer()): ?>
                <a class="btn btn-outline btn-sm" href="<?= e(url_for('account.dashboard')) ?>">
                    <i data-lucide="layout-dashboard"></i> My Portal
                </a>
                <button type="button" class="btn btn-ghost btn-sm" data-logout>Sign Out</button>
            <?php elseif ($currentUser !== []): ?>
                <a class="btn btn-outline btn-sm" href="<?= e(url_for('manage.dashboard')) ?>">
                    <i data-lucide="shield"></i> Admin Console
                </a>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= e(url_for('login')) ?>">Sign In</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url_for('register')) ?>">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main fade-in">