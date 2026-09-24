<?php

declare(strict_types=1);

$pageTitle = 'Notifications';
$pageDesc = 'System activity relevant to you';
$activeNav = 'notifications';
$pageScript = 'js/pages/notifications.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-desc">Stay on top of enquiries, payments and approvals.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-outline" data-mark-all data-permission="notifications.update"><i data-lucide="check-check"></i> Mark All Read</button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <select class="form-control" style="width:auto" data-notification-filter>
                <option value="">All notifications</option>
                <option value="unread">Unread only</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-unread-label></span>
        </div>
    </div>
    <div class="card-pad" id="notification-list">
        <?= loader_skeleton(1) ?>
        <?= loader_skeleton(1) ?>
        <?= loader_skeleton(1) ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

