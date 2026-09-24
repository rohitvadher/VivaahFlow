<?php

declare(strict_types=1);

$pageTitle = 'Activity Log';
$pageDesc = 'Audit trail of every administrative action';
$activeNav = 'activity';
$pageScript = 'js/pages/activity.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Activity Log</h1>
        <p class="page-desc">A chronological record of changes made in the panel.</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search actions or details" data-table-search>
            </div>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="activity-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="activity-pagination"></div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

