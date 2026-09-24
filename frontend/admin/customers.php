<?php

declare(strict_types=1);

$pageTitle = 'Customers';
$pageDesc = 'Manage your customer directory';
$activeNav = 'customers';
$pageScript = 'js/pages/customers.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Customers</h1>
        <p class="page-desc">Keep track of every couple and their celebration details.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-customer data-permission="customers.create">
            <i data-lucide="user-plus"></i> Add Customer
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search by name, email or phone" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="customers-table">
        <div class="card-pad"><?= loader_skeleton(4) ?></div>
    </div>
    <div class="pagination" id="customers-pagination"></div>
</div>

<div class="modal" id="customer-modal" hidden>
    <div class="modal-dialog">
        <form id="customer-form">
            <div class="modal-head">
                <h3 class="card-title" data-modal-title>Add Customer</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label" for="customer-name">Full name</label>
                        <input class="form-control" type="text" id="customer-name" name="name" placeholder="e.g. Aarav Sharma">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div>
                        <label class="label" for="customer-email">Email</label>
                        <input class="form-control" type="email" id="customer-email" name="email" placeholder="name@example.com">
                        <div class="form-error" data-error-for="email"></div>
                    </div>
                    <div>
                        <label class="label" for="customer-phone">Phone</label>
                        <input class="form-control" type="tel" id="customer-phone" name="phone" placeholder="+91 98765 43210">
                        <div class="form-error" data-error-for="phone"></div>
                    </div>
                    <div>
                        <label class="label" for="customer-wedding-date">Wedding date</label>
                        <input class="form-control" type="date" id="customer-wedding-date" name="wedding_date" data-datepicker>
                    </div>
                    <div>
                        <label class="label" for="customer-event-type">Event type</label>
                        <input class="form-control" type="text" id="customer-event-type" name="event_type" placeholder="e.g. Wedding">
                    </div>
                    <div>
                        <label class="label" for="customer-city">City</label>
                        <input class="form-control" type="text" id="customer-city" name="city" placeholder="e.g. Mumbai">
                    </div>
                    <div>
                        <label class="label" for="customer-status">Status</label>
                        <select class="form-control" id="customer-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="full">
                        <label class="label" for="customer-address">Address</label>
                        <input class="form-control" type="text" id="customer-address" name="address" placeholder="Street, area, landmark">
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Customer</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

