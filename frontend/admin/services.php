<?php

declare(strict_types=1);

$pageTitle = 'Services';
$pageDesc = 'Manage the services you offer';
$activeNav = 'services';
$pageScript = 'js/pages/services.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Services</h1>
        <p class="page-desc">Define what you offer, with pricing and imagery.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-service data-permission="services.create">
            <i data-lucide="plus"></i> Add Service
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search services" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-filter-category>
                <option value="">All categories</option>
            </select>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="services-table">
        <div class="card-pad"><?= loader_skeleton(4) ?></div>
    </div>
    <div class="pagination" id="services-pagination"></div>
</div>

<div class="modal" id="service-modal" hidden>
    <div class="modal-dialog modal-lg">
        <form id="service-form">
            <div class="modal-head">
                <h3 class="card-title" data-modal-title>Add Service</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div>
                        <label class="label">Category</label>
                        <select class="form-control" name="category_id" data-category-select></select>
                        <div class="form-error" data-error-for="category_id"></div>
                    </div>
                    <div>
                        <label class="label">Name</label>
                        <input class="form-control" type="text" name="name">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div class="full">
                        <label class="label">Short description</label>
                        <input class="form-control" type="text" name="short_description" maxlength="255">
                    </div>
                    <div class="full">
                        <label class="label">Full description</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                    <div>
                        <label class="label">Starting price (<?= e(app_config('currency.symbol', '₹')) ?>)</label>
                        <input class="form-control" type="number" name="starting_price" min="0" step="0.01">
                        <div class="form-error" data-error-for="starting_price"></div>
                    </div>
                    <div>
                        <label class="label">Duration (minutes)</label>
                        <input class="form-control" type="number" name="duration_minutes" min="0" step="15">
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
                        <label class="checkbox"><input type="checkbox" name="is_featured" value="1"> Feature this service</label>
                    </div>
                    <div class="full">
                        <label class="label">Cover image</label>
                        <div class="dropzone" data-dropzone>
                            <i data-lucide="image-plus"></i>
                            <div>Click or drag an image here</div>
                            <div class="text-xs mt-1">JPG, PNG or WebP up to 3 MB</div>
                        </div>
                        <input type="file" name="image" accept="image/*" hidden data-file-input>
                        <div class="upload-preview hidden" data-preview>
                            <img alt="Preview">
                            <div><div class="font-semibold text-sm" data-preview-name></div><div class="text-muted text-xs">New cover image</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Service</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="gallery-modal" hidden>
    <div class="modal-dialog modal-lg">
        <div class="modal-head">
            <div>
                <h3 class="card-title">Gallery</h3>
                <div class="text-muted text-sm" data-gallery-service></div>
            </div>
            <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div class="flex gap-3 items-end mb-4">
                <div class="flex-1">
                    <label class="label">Add image</label>
                    <input class="form-control" type="file" accept="image/*" data-gallery-file>
                </div>
                <div class="flex-1">
                    <label class="label">Caption</label>
                    <input class="form-control" type="text" data-gallery-caption placeholder="Optional caption">
                </div>
                <button type="button" class="btn btn-primary" data-gallery-upload data-permission="services.update">Upload</button>
            </div>
            <div data-gallery-grid>
                <?= loader_skeleton(1) ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

