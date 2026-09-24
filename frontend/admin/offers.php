<?php

declare(strict_types=1);

$pageTitle = 'Offers';
$pageDesc = 'Run promotional offers and seasonal discounts';
$activeNav = 'offers';
$pageScript = 'js/pages/offers.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Offers</h1>
        <p class="page-desc">Time-bound discounts across services and packages.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-offer data-permission="offers.create">
            <i data-lucide="plus"></i> Add Offer
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search offers" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="offers-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="offers-pagination"></div>
</div>

<div class="modal" id="offer-modal" hidden>
    <div class="modal-dialog modal-lg">
        <form id="offer-form">
            <div class="modal-head">
                <h3 class="card-title" data-modal-title>Add Offer</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Offer name</label>
                        <input class="form-control" type="text" name="name">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div class="full">
                        <label class="label">Description</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                    <div>
                        <label class="label">Discount type</label>
                        <select class="form-control" name="discount_type">
                            <option value="percent">Percentage</option>
                            <option value="fixed">Fixed amount</option>
                        </select>
                        <div class="form-error" data-error-for="discount_type"></div>
                    </div>
                    <div>
                        <label class="label">Discount value</label>
                        <input class="form-control" type="number" name="discount_value" min="0" step="0.01" value="0">
                        <div class="form-error" data-error-for="discount_value"></div>
                    </div>
                    <div>
                        <label class="label">Start date</label>
                        <input class="form-control" type="date" name="start_date">
                        <div class="form-error" data-error-for="start_date"></div>
                    </div>
                    <div>
                        <label class="label">End date</label>
                        <input class="form-control" type="date" name="end_date">
                        <div class="form-error" data-error-for="end_date"></div>
                    </div>
                    <div>
                        <label class="label">Applicable to</label>
                        <select class="form-control" name="applicable_to" data-applicable>
                            <option value="all">All services and packages</option>
                            <option value="services">Selected services</option>
                            <option value="packages">Selected packages</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="full hidden" data-scope-services>
                        <label class="label">Services</label>
                        <div class="border rounded" style="max-height:180px;overflow:auto;padding:12px" data-service-list></div>
                    </div>
                    <div class="full hidden" data-scope-packages>
                        <label class="label">Packages</label>
                        <div class="border rounded" style="max-height:180px;overflow:auto;padding:12px" data-package-list></div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Offer</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

