<?php

declare(strict_types=1);

$pageTitle = 'Quotations';
$pageDesc = 'Prepare, send and convert quotations';
$activeNav = 'quotations';
$pageScript = 'js/pages/quotations.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Quotations</h1>
        <p class="page-desc">Track every quotation from draft to accepted booking.</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search quotations or customers" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
                <option value="expired">Expired</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="quotations-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="quotations-pagination"></div>
</div>

<div class="modal" id="booking-modal" hidden>
    <div class="modal-dialog">
        <form id="booking-form">
            <div class="modal-head">
                <h3 class="card-title">Create Booking</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="quotation_id">
                <p class="text-muted text-sm mb-4" data-booking-summary></p>
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Event date</label>
                        <input class="form-control" type="date" name="event_date" required>
                        <div class="form-error" data-error-for="event_date"></div>
                    </div>
                    <div class="full">
                        <label class="label">Event type</label>
                        <input class="form-control" type="text" name="event_type" placeholder="Wedding, Reception, Engagement">
                    </div>
                    <div class="full">
                        <label class="label">Venue address</label>
                        <input class="form-control" type="text" name="venue_address">
                    </div>
                    <div class="full">
                        <label class="label">Internal notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Create Booking</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

