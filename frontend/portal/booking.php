<?php

declare(strict_types=1);

$id = isset($routeParams['id']) ? (int)$routeParams['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$pageTitle = 'Booking Details';
$pageDesc = 'Everything about your confirmed celebration.';
$activeNav = 'bookings';
$pageScript = 'js/pages/portal/booking.js';

require __DIR__ . '/partials/header.php';
?>
<div class="mb-4">
    <a class="btn btn-ghost btn-sm" href="<?= url_for('account.bookings') ?>">
        <i data-lucide="arrow-left"></i> Back to bookings
    </a>
</div>

<div data-booking data-id="<?= $id ?>">
    <div class="card card-pad">
        <?= loader_skeleton(1, 240) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

