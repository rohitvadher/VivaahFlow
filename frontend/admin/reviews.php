<?php

declare(strict_types=1);

$pageTitle = 'Reviews';
$pageDesc = 'Moderate customer reviews before they appear publicly';
$activeNav = 'reviews';
$pageScript = 'js/pages/reviews.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Reviews</h1>
        <p class="page-desc">Approve reviews to publish them on the public website.</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search reviews or customers" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="reviews-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="reviews-pagination"></div>
</div>

<div class="modal" id="review-modal" hidden>
    <div class="modal-dialog modal-lg">
        <form id="review-form">
            <div class="modal-head">
                <h3 class="card-title">Moderate Review</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div data-review-summary></div>
                <div class="form-grid mt-4">
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_visible" value="1" data-visible-toggle>
                            <label class="label mb-0">Visible on website</label>
                        </div>
                    </div>
                    <div class="full">
                        <label class="label">Public reply</label>
                        <textarea class="form-control" name="reply" rows="4"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Review</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

