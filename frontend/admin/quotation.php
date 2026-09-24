<?php

declare(strict_types=1);

$pageTitle = 'Quotation';
$pageDesc = 'Quotation detail and workflow';
$activeNav = 'quotations';
$pageScript = 'js/pages/quotation.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div class="flex items-center gap-3">
        <a class="icon-btn" href="<?= url_for('manage.quotations') ?>" aria-label="Back"><i data-lucide="arrow-left"></i></a>
        <div>
            <h1 class="page-title" data-quotation-reference>Quotation</h1>
            <p class="page-desc" data-quotation-subtitle>Loading quotation details</p>
        </div>
    </div>
    <div class="page-actions" data-quotation-actions></div>
</div>

<div id="quotation-detail">
    <div class="card"><div class="card-pad"><?= loader_skeleton(2) ?></div></div>
</div>

<div class="modal" id="quotation-edit-modal" hidden>
    <div class="modal-dialog modal-lg">
        <form id="quotation-edit-form">
            <div class="modal-head">
                <h3 class="card-title">Edit Quotation</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="label">Items</label>
                    <div class="border rounded" data-items-editor></div>
                    <div class="flex items-center gap-2 mt-3">
                        <select class="form-control flex-1" data-catalogue-select>
                            <option value="">Add from catalogue</option>
                        </select>
                        <button type="button" class="btn btn-outline" data-add-item><i data-lucide="plus"></i> Add Item</button>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="label">Discount type</label>
                        <select class="form-control" name="discount_type">
                            <option value="">No discount</option>
                            <option value="percent">Percentage</option>
                            <option value="fixed">Fixed amount</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Discount value</label>
                        <input class="form-control" type="number" name="discount_value" min="0" step="0.01" value="0">
                    </div>
                    <div>
                        <label class="label">Valid until</label>
                        <input class="form-control" type="date" name="valid_until">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Quotation</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="booking-modal" hidden>
    <div class="modal-dialog">
        <form id="booking-form">
            <div class="modal-head">
                <h3 class="card-title">Create Booking</h3>
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

