<?php

declare(strict_types=1);

$pageTitle = 'Reviews';
$pageDesc = 'What our customers say about working with us.';
$activeNav = 'reviews';
$pageScript = 'js/pages/public/reviews.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <h1 class="site-section-title">Customer reviews</h1>
        <p class="site-section-sub">Honest feedback from celebrations we have delivered.</p>
    </div>
</section>

<section class="site-section">
    <div class="site-container">
        <div class="grid gap-8 lg:grid-cols-3">
            <aside>
                <div class="card card-pad" data-review-summary>
                    <?= loader_skeleton(1, 120) ?>
                </div>
            </aside>
            <div class="lg:col-span-2">
                <div class="grid gap-4" data-reviews></div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

