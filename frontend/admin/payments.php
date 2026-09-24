<?php

declare(strict_types=1);

$pageTitle = 'Payments';
$pageDesc = 'Every payment received against bookings';
$activeNav = 'payments';
$pageScript = 'js/pages/payments.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Payments</h1>
        <p class="page-desc">Payments update booking balances and invoice status automatically.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-payment data-permission="payments.create"><i data-lucide="plus"></i> Record Payment</button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search payments or bookings" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="recorded">Recorded</option>
                <option value="reversed">Reversed</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="payments-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="payments-pagination"></div>
</div>

<div class="modal" id="payment-modal" hidden>
    <div class="modal-dialog">
        <form id="payment-form">
            <div class="modal-head">
                <h3 class="card-title">Record Payment</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Booking</label>
                        <select class="form-control" name="booking_id" data-booking-select required></select>
                        <div class="form-error" data-error-for="booking_id"></div>
                    </div>
                    <div class="full" data-booking-balance></div>
                    <div>
                        <label class="label">Amount</label>
                        <input class="form-control" type="number" name="amount" min="0" step="0.01" required>
                        <div class="form-error" data-error-for="amount"></div>
                    </div>
                    <div>
                        <label class="label">Date</label>
                        <input class="form-control" type="date" name="payment_date">
                    </div>
                    <div>
                        <label class="label">Method</label>
                        <select class="form-control" name="method">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                        <div class="form-error" data-error-for="method"></div>
                    </div>
                    <div>
                        <label class="label">Reference</label>
                        <input class="form-control" type="text" name="reference_no">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Record Payment</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

