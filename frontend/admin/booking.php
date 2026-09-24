<?php

declare(strict_types=1);

$pageTitle = 'Booking';
$pageDesc = 'Booking detail, schedule and payments';
$activeNav = 'bookings';
$pageScript = 'js/pages/booking.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div class="flex items-center gap-3">
        <a class="icon-btn" href="<?= url_for('manage.bookings') ?>" aria-label="Back"><i data-lucide="arrow-left"></i></a>
        <div>
            <h1 class="page-title" data-booking-reference>Booking</h1>
            <p class="page-desc" data-booking-subtitle>Loading booking details</p>
        </div>
    </div>
    <div class="page-actions" data-booking-actions></div>
</div>

<div id="booking-detail">
    <div class="card"><div class="card-pad"><?= loader_skeleton(2) ?></div></div>
</div>

<div class="modal" id="booking-edit-modal" hidden>
    <div class="modal-dialog">
        <form id="booking-edit-form">
            <div class="modal-head">
                <h3 class="card-title">Edit Booking</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div>
                        <label class="label">Booking date</label>
                        <input class="form-control" type="date" name="booking_date">
                    </div>
                    <div>
                        <label class="label">Event date</label>
                        <input class="form-control" type="date" name="event_date">
                        <div class="form-error" data-error-for="event_date"></div>
                    </div>
                    <div>
                        <label class="label">Event type</label>
                        <input class="form-control" type="text" name="event_type">
                    </div>
                    <div>
                        <label class="label">Venue address</label>
                        <input class="form-control" type="text" name="venue_address">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="event-modal" hidden>
    <div class="modal-dialog">
        <form id="event-form">
            <div class="modal-head">
                <h3 class="card-title" data-event-title>Add Event</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Title</label>
                        <input class="form-control" type="text" name="title" required>
                        <div class="form-error" data-error-for="title"></div>
                    </div>
                    <div>
                        <label class="label">Date</label>
                        <input class="form-control" type="date" name="event_date" required>
                    </div>
                    <div>
                        <label class="label">Venue</label>
                        <input class="form-control" type="text" name="venue_address">
                    </div>
                    <div>
                        <label class="label">Start time</label>
                        <input class="form-control" type="time" name="start_time">
                    </div>
                    <div>
                        <label class="label">End time</label>
                        <input class="form-control" type="time" name="end_time">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Event</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="assign-modal" hidden>    <div class="modal-dialog">
        <form id="assign-form">
            <div class="modal-head">
                <h3 class="card-title">Assign Staff</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-muted text-sm mb-4" data-assign-summary></p>
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Staff member</label>
                        <select class="form-control" name="staff_id" data-staff-select></select>
                        <div class="form-error" data-error-for="staff_id"></div>
                    </div>
                    <div class="full">
                        <label class="label">Role note</label>
                        <input class="form-control" type="text" name="role_note" placeholder="Photographer, Coordinator">
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Assign</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="schedule-modal" hidden>
    <div class="modal-dialog">
        <form id="schedule-form">
            <div class="modal-head">
                <h3 class="card-title">Schedule Booking</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
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

