<?php

declare(strict_types=1);

$pageTitle = 'My Bookings';
$pageDesc = 'Track your confirmed bookings, events and payment progress.';
$activeNav = 'bookings';
$pageScript = 'js/pages/portal/bookings.js';

require __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Bookings</h2>
            <p class="card-subtitle">Confirmed celebrations and their current stage</p>
        </div>
    </div>
    <div class="table-wrap" data-table>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

