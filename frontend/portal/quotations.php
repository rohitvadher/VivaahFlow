<?php

declare(strict_types=1);

$pageTitle = 'My Quotations';
$pageDesc = 'Review proposals prepared for you and accept the one you love.';
$activeNav = 'quotations';
$pageScript = 'js/pages/portal/quotations.js';

require __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Quotations</h2>
            <p class="card-subtitle">Proposals and their current status</p>
        </div>
    </div>
    <div class="table-wrap" data-table>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

