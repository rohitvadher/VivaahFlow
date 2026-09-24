<?php

declare(strict_types=1);

$pageTitle = 'Bookings';
$pageDesc = 'Manage confirmed events and payments';
$activeNav = 'bookings';
$pageScript = 'js/pages/bookings.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Bookings</h1>
        <p class="page-desc">Every accepted quotation becomes a booking here.</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search bookings or customers" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="scheduled">Scheduled</option>
                <option value="in_progress">In progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input class="form-control" type="date" style="width:auto" data-filter-date-from aria-label="From date">
            <input class="form-control" type="date" style="width:auto" data-filter-date-to aria-label="To date">
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="bookings-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="bookings-pagination"></div>
</div>

<div class="modal" id="schedule-modal" hidden>
    <div class="modal-dialog">
        <form id="schedule-form">
            <div class="modal-head">
                <h3 class="card-title">Schedule Booking</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="booking_id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Event date</label>
                        <input class="form-control" type="date" name="event_date" required>
                        <div class="form-error" data-error-for="event_date"></div>
                    </div>
                    <div class="full">
                        <label class="label">Venue address</label>
                        <input class="form-control" type="text" name="venue_address">
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="payment-modal" hidden>
    <div class="modal-dialog">
        <form id="payment-form">
            <div class="modal-head">
                <h3 class="card-title">Record Payment</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="booking_id">
                <p class="text-muted text-sm mb-4" data-payment-summary></p>
                <div class="form-grid">
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

