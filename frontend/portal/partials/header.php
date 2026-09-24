<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

portal_guard();

use App\Helpers\AssetManager;

$pageTitle = $pageTitle ?? 'My Portal';
$pageDesc = $pageDesc ?? '';
$activeNav = $activeNav ?? 'dashboard';
$pageScript = $pageScript ?? '';
$extraCss = $extraCss ?? [];
$extraLibraries = $extraLibraries ?? [];
$areaScripts = $areaScripts ?? [];
$currentUser = site_user();
$brandName = (string)app_config('app_name', 'VivaahFlow');

$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => url_for('account.dashboard')],
    'enquiries' => ['label' => 'Enquiries', 'icon' => 'messages-square', 'href' => url_for('account.enquiries')],
    'quotations' => ['label' => 'Quotations', 'icon' => 'file-text', 'href' => url_for('account.quotations')],
    'bookings' => ['label' => 'Bookings', 'icon' => 'calendar-check', 'href' => url_for('account.bookings')],
    'payments' => ['label' => 'Payments', 'icon' => 'wallet', 'href' => url_for('account.payments')],
    'invoices' => ['label' => 'Invoices', 'icon' => 'receipt', 'href' => url_for('account.invoices')],
    'reviews' => ['label' => 'Reviews', 'icon' => 'star', 'href' => url_for('account.reviews')],
    'profile' => ['label' => 'Profile', 'icon' => 'user-round', 'href' => url_for('account.profile')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; <?= e($brandName) ?></title>
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
        <div class="site-actions">
            <a class="btn btn-ghost btn-sm" href="<?= url_for('home') ?>">
                <i data-lucide="arrow-left"></i> Visit Website
            </a>
            <span class="avatar avatar-sm" title="<?= e($currentUser['name'] ?? '') ?>"><?= e(site_initials((string)($currentUser['name'] ?? '?'))) ?></span>
            <button type="button" class="btn btn-outline btn-sm" data-logout>Sign Out</button>
        </div>
    </div>
    <nav class="portal-nav">
        <div class="site-container portal-nav-inner">
            <?php foreach ($navItems as $key => $item): ?>
                <a class="portal-nav-link<?= $activeNav === $key ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>">
                    <i data-lucide="<?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
<main class="site-main fade-in">
    <div class="site-container" style="padding-top:28px;padding-bottom:48px">
        <div class="mb-6">
            <h1 class="site-section-title" style="font-size:26px"><?= e($pageTitle) ?></h1>
            <?php if ($pageDesc !== ''): ?>
                <p class="site-section-sub"><?= e($pageDesc) ?></p>
            <?php endif; ?>
        </div>