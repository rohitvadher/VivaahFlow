<?php

declare(strict_types=1);

$pageTitle = 'Payments';
$pageDesc = 'Every payment recorded against your bookings.';
$activeNav = 'payments';
$pageScript = 'js/pages/portal/payments.js';

require __DIR__ . '/partials/header.php';
?>
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6" data-summary>
    <div class="card card-pad">
        <div class="stat-label">Total paid</div>
        <div class="stat-value" data-total-paid>&mdash;</div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Payment history</h2>
            <p class="card-subtitle">Recorded installments and their references</p>
        </div>
    </div>
    <div class="table-wrap" data-table>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

