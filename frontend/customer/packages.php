<?php

declare(strict_types=1);

$pageTitle = 'Packages';
$pageDesc = 'Bundled wedding packages offering better value across services.';
$activeNav = 'packages';
$pageScript = 'js/pages/public/packages.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <h1 class="site-section-title">Wedding packages</h1>
        <p class="site-section-sub">Thoughtfully bundled services that save you time and money.</p>
    </div>
</section>

<section class="site-section">
    <div class="site-container">
        <div class="site-grid" data-packages></div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

