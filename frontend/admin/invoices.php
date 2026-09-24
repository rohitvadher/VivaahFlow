<?php

declare(strict_types=1);

$pageTitle = 'Invoices';
$pageDesc = 'Generated invoices and their settlement status';
$activeNav = 'invoices';
$pageScript = 'js/pages/invoices.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Invoices</h1>
        <p class="page-desc">Invoices are generated from bookings and settle automatically as payments arrive.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-invoice data-permission="invoices.create"><i data-lucide="plus"></i> Generate Invoice</button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search invoices or customers" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="issued">Issued</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="invoices-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="invoices-pagination"></div>
</div>

<div class="modal" id="invoice-modal" hidden>
    <div class="modal-dialog">
        <form id="invoice-form">
            <div class="modal-head">
                <h3 class="card-title">Generate Invoice</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Booking</label>
                        <select class="form-control" name="booking_id" data-booking-select required></select>
                        <div class="form-error" data-error-for="booking_id"></div>
                    </div>
                    <div>
                        <label class="label">Issue date</label>
                        <input class="form-control" type="date" name="issue_date">
                    </div>
                    <div>
                        <label class="label">Due date</label>
                        <input class="form-control" type="date" name="due_date">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Generate Invoice</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

