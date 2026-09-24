<?php

declare(strict_types=1);

$pageTitle = 'Enquiries';
$pageDesc = 'Website and walk-in enquiries';
$activeNav = 'enquiries';
$pageScript = 'js/pages/enquiries.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Enquiries</h1>
        <p class="page-desc">Track every enquiry from first contact to conversion.</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search reference, customer or venue" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="quotation_pending">Quotation Pending</option>
                <option value="quotation_sent">Quotation Sent</option>
                <option value="converted">Converted</option>
                <option value="closed">Closed</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="enquiries-table">
        <div class="card-pad"><?= loader_skeleton(4) ?></div>
    </div>
    <div class="pagination" id="enquiries-pagination"></div>
</div>

<div class="modal" id="enquiry-modal" hidden>
    <div class="modal-dialog modal-lg">
        <div class="modal-head">
            <div>
                <h3 class="card-title" data-enquiry-ref>Enquiry</h3>
                <div class="text-muted text-sm" data-enquiry-customer></div>
            </div>
            <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" data-enquiry-body>
            <?= loader_skeleton(2) ?>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-outline" data-enquiry-lead data-permission="enquiries.convert">
                <i data-lucide="target"></i> Create Lead
            </button>
            <button type="button" class="btn btn-primary" data-enquiry-quotation data-permission="enquiries.convert">
                <i data-lucide="file-plus"></i> Create Quotation
            </button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

