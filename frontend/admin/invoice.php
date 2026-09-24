<?php

declare(strict_types=1);

$pageTitle = 'Invoice';
$pageDesc = 'Invoice detail and settlement';
$activeNav = 'invoices';
$pageScript = 'js/pages/invoice.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div class="flex items-center gap-3">
        <a class="icon-btn" href="<?= url_for('manage.invoices') ?>" aria-label="Back"><i data-lucide="arrow-left"></i></a>
        <div>
            <h1 class="page-title" data-invoice-number>Invoice</h1>
            <p class="page-desc" data-invoice-subtitle>Loading invoice</p>
        </div>
    </div>
    <div class="page-actions" data-invoice-actions></div>
</div>

<div id="invoice-detail">
    <div class="card"><div class="card-pad"><?= loader_skeleton(2) ?></div></div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

