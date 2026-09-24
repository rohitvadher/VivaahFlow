<?php

declare(strict_types=1);

$pageTitle = 'Customer Details';
$pageDesc = 'Complete profile and history';
$activeNav = 'customers';
$pageScript = 'js/pages/customer.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <a class="link-brand text-sm inline-flex items-center gap-1" href="<?= url_for('manage.customers') ?>">
            <i data-lucide="arrow-left" style="width:15px;height:15px"></i> Back to customers
        </a>
        <h1 class="page-title mt-2" data-customer-name>&mdash;</h1>
        <div class="flex items-center gap-2 mt-2" data-customer-meta></div>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-outline" data-edit-customer data-permission="customers.update">
            <i data-lucide="pencil"></i> Edit
        </button>
    </div>
</div>

<div class="grid-stats mb-6">
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="calendar-check"></i></div>
        <div class="stat-value" data-stat="bookings">&mdash;</div>
        <div class="stat-label">Bookings</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="wallet"></i></div>
        <div class="stat-value" data-stat="paid">&mdash;</div>
        <div class="stat-label">Total Paid</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="file-text"></i></div>
        <div class="stat-value" data-stat="quotations">&mdash;</div>
        <div class="stat-label">Quotations</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i data-lucide="star"></i></div>
        <div class="stat-value" data-stat="reviews">&mdash;</div>
        <div class="stat-label">Reviews</div>
    </div>
</div>

<div class="grid-cards mb-6">
    <div class="card">
        <div class="card-head"><div class="card-title">Profile</div></div>
        <div class="card-pad">
            <dl class="detail-list" data-customer-details>
                <?= loader_skeleton(3) ?>
            </dl>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Recent Bookings</div></div>
        <div data-section="bookings"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-head"><div class="card-title">Quotations</div></div>
    <div data-section="quotations"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
</div>

<div class="grid-cards">
    <div class="card">
        <div class="card-head"><div class="card-title">Payments</div></div>
        <div data-section="payments"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Reviews</div></div>
        <div data-section="reviews"><div class="card-pad"><?= loader_skeleton(1) ?></div></div>
    </div>
</div>

<div class="modal" id="customer-modal" hidden>
    <div class="modal-dialog">
        <form id="customer-form">
            <div class="modal-head">
                <h3 class="card-title">Edit Customer</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Full name</label>
                        <input class="form-control" type="text" name="name">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input class="form-control" type="email" name="email">
                        <div class="form-error" data-error-for="email"></div>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input class="form-control" type="tel" name="phone">
                        <div class="form-error" data-error-for="phone"></div>
                    </div>
                    <div>
                        <label class="label">Wedding date</label>
                        <input class="form-control" type="date" name="wedding_date">
                    </div>
                    <div>
                        <label class="label">Event type</label>
                        <input class="form-control" type="text" name="event_type">
                    </div>
                    <div>
                        <label class="label">City</label>
                        <input class="form-control" type="text" name="city">
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="full">
                        <label class="label">Address</label>
                        <input class="form-control" type="text" name="address">
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
<?php require __DIR__ . '/partials/footer.php'; ?>

