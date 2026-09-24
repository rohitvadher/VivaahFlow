<?php

declare(strict_types=1);

$id = isset($routeParams['id']) ? (int)$routeParams['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$pageTitle = 'Quotation Details';
$pageDesc = 'Review and respond to your proposal.';
$activeNav = 'quotations';
$pageScript = 'js/pages/portal/quotation.js';

require __DIR__ . '/partials/header.php';
?>
<div class="mb-4">
    <a class="btn btn-ghost btn-sm" href="<?= url_for('account.quotations') ?>">
        <i data-lucide="arrow-left"></i> Back to quotations
    </a>
</div>

<div data-quotation data-id="<?= $id ?>">
    <div class="card card-pad">
        <?= loader_skeleton(1, 220) ?>
    </div>
</div>

<div class="modal" id="reject-quotation-modal" hidden>
    <div class="modal-dialog">
        <div class="modal-head">
            <h3 class="modal-title">Reject quotation</h3>
            <button type="button" class="icon-btn" data-modal-close><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3">Let us know why so we can improve your proposal.</p>
            <div class="form-control">
                <label class="label" for="reject-reason">Reason (optional)</label>
                <textarea class="form-control" id="reject-reason" name="reason" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-danger" data-reject-confirm>Reject quotation</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

