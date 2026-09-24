<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';
$pageDesc = 'Business performance at a glance';
$activeNav = 'dashboard';
$extraLibraries = ['apexcharts'];
$pageScript = 'js/pages/dashboard.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Welcome back, <span data-user-first-name>there</span></h1>
        <p class="page-desc">Here is what is happening across your wedding business today.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline" href="<?= url_for('manage.reports') ?>">
            <i data-lucide="chart-column"></i> View Reports
        </a>
        <a class="btn btn-primary" href="<?= url_for('manage.enquiries') ?>" data-permission="enquiries.read">
            <i data-lucide="plus"></i> New Enquiry
        </a>
    </div>
</div>

<div class="grid-stats mb-6">
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="users"></i></div>
        <div class="stat-value" data-stat="customers_total">&mdash;</div>
        <div class="stat-label">Total Customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="messages-square"></i></div>
        <div class="stat-value" data-stat="enquiries_total">&mdash;</div>
        <div class="stat-label">Total Enquiries</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="calendar-check"></i></div>
        <div class="stat-value" data-stat="active_bookings">&mdash;</div>
        <div class="stat-label">Active Bookings</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="wallet"></i></div>
        <div class="stat-value" data-stat="total_revenue">&mdash;</div>
        <div class="stat-label">Total Revenue</div>
    </div>
</div>

<div class="grid-cards mb-6">
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Revenue Trend</div>
                <div class="card-subtitle">Recorded payments over the last 6 months</div>
            </div>
            <span class="badge badge-brand badge-plain">6 months</span>
        </div>
        <div class="card-pad">
            <div id="revenue-chart" class="chart-box"></div>
        </div>
    </div>
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Booking Status</div>
                <div class="card-subtitle">Distribution of all bookings</div>
            </div>
        </div>
        <div class="card-pad">
            <div id="booking-status-chart" class="chart-box"></div>
        </div>
    </div>
</div>

<div class="grid-cards mb-6">
    <div class="card">
        <div class="card-head">
            <div class="card-title">Upcoming Bookings</div>
            <a class="link-brand text-sm" href="<?= url_for('manage.bookings') ?>">View all</a>
        </div>
        <div data-widget="upcoming-bookings">
            <div class="card-pad"><?= loader_skeleton(3) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-head">
            <div class="card-title">Top Customers</div>
            <a class="link-brand text-sm" href="<?= url_for('manage.customers') ?>">View all</a>
        </div>
        <div data-widget="top-customers">
            <div class="card-pad"><?= loader_skeleton(3) ?></div>
        </div>
    </div>
</div>

<div class="grid-cards">
    <div class="card">
        <div class="card-head">
            <div class="card-title">Service Performance</div>
            <a class="link-brand text-sm" href="<?= url_for('manage.services') ?>">View services</a>
        </div>
        <div class="card-pad">
            <div id="service-performance-chart" class="chart-box"></div>
        </div>
    </div>
    <div class="card">
        <div class="card-head">
            <div class="card-title">Recent Activity</div>
            <a class="link-brand text-sm" href="<?= url_for('manage.activity') ?>">View log</a>
        </div>
        <div class="card-pad" data-widget="recent-activity">
            <?= loader_skeleton(3) ?>
        </div>
    </div>
</div>

<div class="grid-3 mt-6">
    <div class="card card-pad">
        <div class="flex items-center gap-3">
            <span class="stat-icon" style="margin:0"><i data-lucide="file-text"></i></span>
            <div>
                <div class="stat-value" style="font-size:22px" data-stat="pending_quotations">&mdash;</div>
                <div class="stat-label">Pending Quotations</div>
            </div>
        </div>
    </div>
    <div class="card card-pad">
        <div class="flex items-center gap-3">
            <span class="stat-icon" style="margin:0"><i data-lucide="alarm-clock"></i></span>
            <div>
                <div class="stat-value" style="font-size:22px" data-stat="pending_followups">&mdash;</div>
                <div class="stat-label">Pending Follow-ups</div>
            </div>
        </div>
    </div>
    <div class="card card-pad">
        <div class="flex items-center gap-3">
            <span class="stat-icon" style="margin:0"><i data-lucide="badge-percent"></i></span>
            <div>
                <div class="stat-value" style="font-size:22px" data-stat="active_offers">&mdash;</div>
                <div class="stat-label">Active Offers</div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
