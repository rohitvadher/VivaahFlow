<?php

declare(strict_types=1);

$activeNav = $activeNav ?? 'dashboard';
$brandName = (string)app_config('app_name', 'VivaahFlow');
$user = admin_user();
$navGroups = [
    'Overview' => [
        ['dashboard', 'Dashboard', 'manage.dashboard', 'layout-dashboard', 'dashboard.view'],
    ],
    'Clients' => [
        ['customers', 'Customers', 'manage.customers', 'users', 'customers.read'],
        ['enquiries', 'Enquiries', 'manage.enquiries', 'messages-square', 'enquiries.read'],
        ['leads', 'Leads', 'manage.leads', 'target', 'leads.read'],
    ],
    'Catalogue' => [
        ['categories', 'Categories', 'manage.categories', 'tags', 'categories.read'],
        ['services', 'Services', 'manage.services', 'sparkles', 'services.read'],
        ['packages', 'Packages', 'manage.packages', 'package', 'packages.read'],
        ['offers', 'Offers', 'manage.offers', 'badge-percent', 'offers.read'],
    ],
    'Sales' => [
        ['quotations', 'Quotations', 'manage.quotations', 'file-text', 'quotations.read'],
        ['bookings', 'Bookings', 'manage.bookings', 'calendar-check', 'bookings.read'],
        ['events', 'Events', 'manage.events', 'calendar-days', 'events.read'],
    ],
    'Finance' => [
        ['payments', 'Payments', 'manage.payments', 'wallet', 'payments.read'],
        ['invoices', 'Invoices', 'manage.invoices', 'receipt', 'invoices.read'],
    ],
    'Operations' => [
        ['staff', 'Staff', 'manage.staff', 'user-cog', 'staff.read'],
        ['reviews', 'Reviews', 'manage.reviews', 'star', 'reviews.read'],
        ['reports', 'Reports', 'manage.reports', 'chart-column', 'reports.read'],
    ],
    'System' => [
        ['notifications', 'Notifications', 'manage.notifications', 'bell', 'notifications.read'],
        ['users', 'Users', 'manage.users', 'shield', 'users.read'],
        ['settings', 'Settings', 'manage.settings', 'settings', 'settings.read'],
        ['activity', 'Activity Log', 'manage.activity', 'history', 'activity.read'],
    ],
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img class="brand-mark-img" src="<?= asset_url('images/brand/logo.png') ?>" alt="<?= e($brandName) ?> logo" width="40" height="40">
        <span class="brand-text">
            <span class="brand-name"><?= e($brandName) ?></span>
            <span class="brand-role">Admin Panel</span>
        </span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navGroups as $groupLabel => $items): ?>
            <?php
            $visible = array_values(array_filter($items, fn(array $item): bool => admin_can($item[4])));
            if ($visible === []) {
                continue;
            }
            ?>
            <div class="nav-section">
                <div class="nav-label"><?= e($groupLabel) ?></div>
                <?php foreach ($visible as $item): ?>
                    <a class="nav-link<?= $activeNav === $item[0] ? ' is-active' : '' ?>" href="<?= url_for($item[2]) ?>">
                        <i data-lucide="<?= e($item[3]) ?>"></i>
                        <span><?= e($item[1]) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="nav-section">
            <div class="nav-label">Support</div>
            <a class="nav-link<?= $activeNav === 'help' ? ' is-active' : '' ?>" href="<?= url_for('manage.help') ?>">
                <i data-lucide="circle-help"></i>
                <span>Help Center</span>
            </a>
        </div>
    </nav>
    <div class="sidebar-foot">
        <div class="flex items-center gap-2">
            <span class="avatar avatar-sm"><?= e(initials_of($user['name'])) ?></span>
            <div class="min-w-0">
                <div class="truncate" style="color:var(--ink-700);font-weight:600"><?= e($user['name']) ?></div>
                <div class="truncate"><?= e(role_label($user['role'])) ?></div>
            </div>
        </div>
    </div>
</aside>
<div class="sidebar-backdrop"></div>