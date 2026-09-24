<?php

declare(strict_types=1);

$pageTitle = 'My Enquiries';
$pageDesc = 'Track every request you have sent to our planning team.';
$activeNav = 'enquiries';
$pageScript = 'js/pages/portal/enquiries.js';

require __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Enquiry history</h2>
            <p class="card-subtitle">Reference numbers, events and current status</p>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= url_for('contact') ?>">
            <i data-lucide="plus"></i> New Enquiry
        </a>
    </div>
    <div class="table-wrap" data-table>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

