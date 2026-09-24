<?php

declare(strict_types=1);

$pageTitle = 'Categories';
$pageDesc = 'Organise your services into categories';
$activeNav = 'categories';
$pageScript = 'js/pages/categories.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Categories</h1>
        <p class="page-desc">Group services so customers can browse easily.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-category data-permission="categories.create">
            <i data-lucide="plus"></i> Add Category
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search categories" data-table-search>
            </div>
        </div>
    </div>
    <div id="categories-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
</div>

<div class="modal" id="category-modal" hidden>
    <div class="modal-dialog">
        <form id="category-form">
            <div class="modal-head">
                <h3 class="card-title" data-modal-title>Add Category</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Category name</label>
                        <input class="form-control" type="text" name="name" placeholder="e.g. Photography & Videography">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div class="full">
                        <label class="label">Description</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                    <div>
                        <label class="label">Display order</label>
                        <input class="form-control" type="number" name="display_order" value="0" min="0">
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Category</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

