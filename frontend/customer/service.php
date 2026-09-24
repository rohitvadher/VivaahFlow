<?php

declare(strict_types=1);

$slug = isset($routeParams['slug']) ? (string)$routeParams['slug'] : (isset($_GET['slug']) ? (string)$_GET['slug'] : '');

$pageTitle = 'Service';
$pageDesc = 'Service details and pricing.';
$activeNav = 'services';
$pageScript = 'js/pages/public/service.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <nav class="text-sm text-muted mb-4">
            <a href="<?= url_for('home') ?>">Home</a>
            <span class="mx-1">/</span>
            <a href="<?= url_for('services') ?>">Services</a>
            <span class="mx-1">/</span>
            <span data-breadcrumb>Details</span>
        </nav>
    </div>
</section>

<div data-service-detail data-slug="<?= e($slug) ?>">
    <section class="site-section" style="padding-top:0">
        <div class="site-container">
            <div class="card" data-service-main>
                <?= loader_skeleton(1, 340) ?>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

