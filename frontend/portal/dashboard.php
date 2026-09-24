<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';
$pageDesc = 'A quick snapshot of your enquiries, quotations and bookings.';
$activeNav = 'dashboard';
$pageScript = 'js/pages/portal/dashboard.js';

require __DIR__ . '/partials/header.php';
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-stats>
    <?= loader_skeleton(1, 100) ?>
    <?= loader_skeleton(1, 100) ?>
    <?= loader_skeleton(1, 100) ?>
    <?= loader_skeleton(1, 100) ?>
</div>

<div class="grid gap-6 lg:grid-cols-3 mt-6">
    <div class="lg:col-span-2" data-upcoming></div>
    <div data-quick></div>
</div>

<div class="card mt-6">
    <div class="card-head">
        <div>
            <h2 class="card-title">Recent enquiries</h2>
            <p class="card-subtitle">Your latest requests to our team</p>
        </div>
        <a class="btn btn-soft btn-sm" href="<?= url_for('account.enquiries') ?>">View all</a>
    </div>
    <div class="table-wrap" data-recent-enquiries>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
