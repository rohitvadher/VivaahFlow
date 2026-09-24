<?php

declare(strict_types=1);

$pageTitle = 'Reports';
$pageDesc = 'Revenue, conversion and performance analytics';
$activeNav = 'reports';
$pageScript = 'js/pages/reports.js';
$extraLibraries = ['apexcharts'];

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-desc">Figures are calculated from payments, bookings and quotations.</p>
    </div>
    <div class="page-actions">
        <select class="form-control" style="width:auto" data-range>
            <option value="3m">Last 3 months</option>
            <option value="6m" selected>Last 6 months</option>
            <option value="12m">Last 12 months</option>
        </select>
    </div>
</div>

<div class="grid-stats" id="report-kpis">
    <div class="card"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    <div class="card"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    <div class="card"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    <div class="card"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
</div>

<div class="grid-3 mt-4">
    <div class="grid-span-2 card">
        <div class="card-head"><h2 class="card-title">Revenue Trend</h2></div>
        <div class="card-pad"><div id="chart-revenue"></div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Booking Status</h2></div>
        <div class="card-pad"><div id="chart-booking-status"></div></div>
    </div>
</div>

<div class="grid-3 mt-4">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Quotation Conversion</h2></div>
        <div class="card-pad"><div id="chart-quotation"></div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Lead Conversion</h2></div>
        <div class="card-pad"><div id="chart-lead"></div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Service Performance</h2></div>
        <div class="card-pad"><div id="chart-services"></div></div>
    </div>
</div>

<div class="grid-3 mt-4">
    <div class="grid-span-2 card">
        <div class="card-head">
            <h2 class="card-title">Revenue Report</h2>
            <div class="toolbar">
                <input class="form-control" type="date" style="width:auto" data-revenue-from aria-label="From">
                <input class="form-control" type="date" style="width:auto" data-revenue-to aria-label="To">
                <button type="button" class="btn btn-sm btn-outline" data-revenue-apply>Apply</button>
            </div>
        </div>
        <div id="revenue-table"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Top Customers</h2></div>
        <div class="card-pad" id="top-customers"><?= loader_skeleton(1) ?></div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-head">
        <h2 class="card-title">Bookings Report</h2>
        <div class="toolbar">
            <input class="form-control" type="date" style="width:auto" data-bookings-from aria-label="From">
            <input class="form-control" type="date" style="width:auto" data-bookings-to aria-label="To">
            <button type="button" class="btn btn-sm btn-outline" data-bookings-apply>Apply</button>
        </div>
    </div>
    <div id="bookings-table"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

