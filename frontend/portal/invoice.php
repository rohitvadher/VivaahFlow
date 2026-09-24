<?php

declare(strict_types=1);

$id = isset($routeParams['id']) ? (int)$routeParams['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$pageTitle = 'Invoice Details';
$pageDesc = 'Review and print your invoice.';
$activeNav = 'invoices';
$pageScript = 'js/pages/portal/invoice.js';

require __DIR__ . '/partials/header.php';
?>
<div class="flex items-center justify-between gap-3 flex-wrap mb-4">
    <a class="btn btn-ghost btn-sm" href="<?= url_for('account.invoices') ?>">
        <i data-lucide="arrow-left"></i> Back to invoices
    </a>
    <button type="button" class="btn btn-outline btn-sm" data-print>
        <i data-lucide="printer"></i> Print
    </button>
</div>

<div data-invoice data-id="<?= $id ?>">
    <div class="card card-pad">
        <?= loader_skeleton(1, 300) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

