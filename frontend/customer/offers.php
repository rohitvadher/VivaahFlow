<?php

declare(strict_types=1);

$pageTitle = 'Offers';
$pageDesc = 'Current discounts and seasonal offers on our services.';
$activeNav = 'offers';
$pageScript = 'js/pages/public/offers.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <h1 class="site-section-title">Offers &amp; savings</h1>
        <p class="site-section-sub">Limited-time discounts to make your celebration sweeter.</p>
    </div>
</section>

<section class="site-section">
    <div class="site-container">
        <div class="site-grid" data-offers></div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

