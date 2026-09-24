<?php

declare(strict_types=1);

$pageTitle = 'Services';
$pageDesc = 'Explore our complete range of wedding and event services.';
$activeNav = 'services';
$pageScript = 'js/pages/public/services.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <h1 class="site-section-title">Our services</h1>
        <p class="site-section-sub">Browse everything we offer and shortlist what fits your celebration.</p>
    </div>
</section>

<section class="site-section is-tight">
    <div class="site-container">
        <div class="toolbar" style="background:#fff;border:1px solid var(--ink-200);border-radius:var(--radius);padding:14px">
            <div class="search-box input-group" style="flex:1;max-width:420px">
                <span class="input-icon" aria-hidden="true"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search services" data-service-search aria-label="Search services" autocomplete="off">
            </div>
        </div>
        <div class="site-filters mt-4" data-categories></div>
    </div>
</section>

<section class="site-section" style="padding-top:0">
    <div class="site-container">
        <div class="site-grid" data-services></div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
