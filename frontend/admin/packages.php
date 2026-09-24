<?php

declare(strict_types=1);

$pageTitle = 'Packages';
$pageDesc = 'Bundle services into attractive packages';
$activeNav = 'packages';
$pageScript = 'js/pages/packages.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Packages</h1>
        <p class="page-desc">Combine services and apply discounts to create offers.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-package data-permission="packages.create">
            <i data-lucide="plus"></i> Add Package
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search packages" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="packages-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="packages-pagination"></div>
</div>

<div class="modal" id="package-modal" hidden>
    <div class="modal-dialog modal-lg">
        <form id="package-form">
            <div class="modal-head">
                <h3 class="card-title" data-modal-title>Add Package</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Package name</label>
                        <input class="form-control" type="text" name="name">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div class="full">
                        <label class="label">Description</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                    <div>
                        <label class="label">Discount type</label>
                        <select class="form-control" name="discount_type" data-discount-type>
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
                        <label class="label">Display order</label>
                        <input class="form-control" type="number" name="display_order" min="0" value="0">
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="full">
                        <label class="checkbox"><input type="checkbox" name="is_featured" value="1"> Feature this package</label>
                    </div>
                    <div class="full">
                        <label class="label">Included services</label>
                        <div class="border rounded" style="max-height:220px;overflow:auto;padding:12px" data-service-list>
                            <?= loader_skeleton(1) ?>
                        </div>
                        <div class="form-error" data-error-for="service_ids"></div>
                    </div>
                    <div class="full">
                        <label class="label">Cover image</label>
                        <div class="dropzone" data-dropzone>
                            <i data-lucide="image-plus"></i>
                            <div>Click or drag an image here</div>
                        </div>
                        <input type="file" name="cover_image" accept="image/*" hidden data-file-input>
                        <div class="upload-preview hidden" data-preview>
                            <img alt="Preview">
                            <div><div class="font-semibold text-sm" data-preview-name></div><div class="text-muted text-xs">New cover image</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Package</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

