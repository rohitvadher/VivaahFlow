<?php

declare(strict_types=1);

$pageTitle = 'Gallery';
$pageDesc = 'Moments from celebrations we have planned and executed.';
$activeNav = 'gallery';
$pageScript = 'js/pages/public/gallery.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <h1 class="site-section-title">Gallery</h1>
        <p class="site-section-sub">A glimpse into the celebrations we bring to life.</p>
    </div>
</section>

<section class="site-section">
    <div class="site-container">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-gallery></div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

