<?php

declare(strict_types=1);

$pageTitle = 'Invoices';
$pageDesc = 'Download and review invoices raised for your bookings.';
$activeNav = 'invoices';
$pageScript = 'js/pages/portal/invoices.js';

require __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Invoices</h2>
            <p class="card-subtitle">Billing documents and their payment status</p>
        </div>
    </div>
    <div class="table-wrap" data-table>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

