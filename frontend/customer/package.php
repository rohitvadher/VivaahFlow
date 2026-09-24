<?php

declare(strict_types=1);

$slug = isset($routeParams['slug']) ? (string)$routeParams['slug'] : (isset($_GET['slug']) ? (string)$_GET['slug'] : '');

$pageTitle = 'Package';
$pageDesc = 'Package inclusions and pricing.';
$activeNav = 'packages';
$pageScript = 'js/pages/public/package.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <nav class="text-sm text-muted mb-4">
            <a href="<?= url_for('home') ?>">Home</a>
            <span class="mx-1">/</span>
            <a href="<?= url_for('packages') ?>">Packages</a>
            <span class="mx-1">/</span>
            <span data-breadcrumb>Details</span>
        </nav>
    </div>
</section>

<div data-package-detail data-slug="<?= e($slug) ?>">
    <section class="site-section" style="padding-top:0">
        <div class="site-container">
            <div class="card card-pad">
                <?= loader_skeleton(1, 300) ?>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

