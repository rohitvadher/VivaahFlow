<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Helpers\AssetManager;

$pageTitle = $pageTitle ?? 'Dashboard';
$pageDesc = $pageDesc ?? '';
$activeNav = $activeNav ?? 'dashboard';
$pageScript = $pageScript ?? '';
$extraCss = $extraCss ?? [];
$extraLibraries = $extraLibraries ?? [];
$areaScripts = $areaScripts ?? [];
$user = admin_user();
$brandName = (string)app_config('app_name', 'VivaahFlow');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; <?= e($brandName) ?></title>
    <?= AssetManager::head($extraCss) ?>
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
<body>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="app-main">
        <header class="topbar">
            <button type="button" class="icon-btn md:hidden" data-sidebar-toggle aria-label="Toggle navigation">
                <i data-lucide="menu"></i>
            </button>
            <div class="min-w-0">
                <div class="topbar-title truncate"><?= e($pageTitle) ?></div>
                <?php if ($pageDesc !== ''): ?>
                    <div class="topbar-sub truncate"><?= e($pageDesc) ?></div>
                <?php endif; ?>
            </div>
            <div class="topbar-actions">
                <a class="icon-btn" href="<?= url_for('manage.notifications') ?>" aria-label="Notifications">
                    <i data-lucide="bell"></i>
                    <span class="dot hidden" data-notification-dot></span>
                </a>
                <div class="dropdown">
                    <button type="button" class="user-chip" data-menu-toggle>
                        <span class="avatar avatar-sm"><?= e(initials_of($user['name'])) ?></span>
                        <span class="text-left">
                            <span class="user-chip-name"><?= e($user['name']) ?></span>
                            <span class="user-chip-role"><?= e(role_label($user['role'])) ?></span>
                        </span>
                        <i data-lucide="chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="<?= url_for('manage.profile') ?>">
                            <i data-lucide="user"></i> My Profile
                        </a>
                        <a class="dropdown-item" href="<?= url_for('home') ?>" target="_blank" rel="noopener">
                            <i data-lucide="external-link"></i> View Website
                        </a>
                        <button type="button" class="dropdown-item is-danger" data-logout>
                            <i data-lucide="log-out"></i> Sign Out
                        </button>
                    </div>
                </div>
            </div>
        </header>
        <main class="page fade-in">