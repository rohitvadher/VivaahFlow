<?php

declare(strict_types=1);

$pageTitle = 'My Reviews';
$pageDesc = 'Share your experience and track your submitted reviews.';
$activeNav = 'reviews';
$pageScript = 'js/pages/portal/reviews.js';

require __DIR__ . '/partials/header.php';
?>
<div class="card mb-6" data-eligible-card hidden>
    <div class="card-head">
        <div>
            <h2 class="card-title">Ready for your review</h2>
            <p class="card-subtitle">Completed bookings you can rate</p>
        </div>
    </div>
    <div class="card-pad" data-eligible></div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Your reviews</h2>
            <p class="card-subtitle">Submitted feedback and our replies</p>
        </div>
    </div>
    <div class="table-wrap" data-table>
        <?= loader_skeleton(1) ?>
    </div>
</div>

<div class="modal" id="review-modal" hidden>
    <div class="modal-dialog">
        <div class="modal-head">
            <h3 class="modal-title">Write a review</h3>
            <button type="button" class="icon-btn" data-modal-close><i data-lucide="x"></i></button>
        </div>
        <form data-review-form novalidate>
            <div class="modal-body">
                <input type="hidden" name="booking_id">
                <div class="form-control">
                    <label class="label">Rating</label>
                    <div class="flex gap-1" data-rating-stars style="font-size:22px">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="icon-btn" data-rating="<?= $i ?>" aria-label="<?= $i ?> star"><i data-lucide="star"></i></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" value="5">
                </div>
                <div class="form-control mt-4">
                    <label class="label" for="review-title">Title</label>
                    <input class="form-control" id="review-title" name="title" maxlength="160">
                    <div class="form-error" data-error-for="title"></div>
                </div>
                <div class="form-control mt-4">
                    <label class="label" for="review-comment">Your experience *</label>
                    <textarea class="form-control" id="review-comment" name="comment" rows="4"></textarea>
                    <div class="form-error" data-error-for="comment"></div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Submit review</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

