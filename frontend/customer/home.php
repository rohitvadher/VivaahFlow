<?php

declare(strict_types=1);

$pageTitle = 'Home';
$pageDesc = 'Wedding planning, venue, catering, decor and entertainment services under one roof.';
$activeNav = 'home';
$pageScript = 'js/pages/public/home.js';

require __DIR__ . '/partials/header.php';
?>
<div class="home">
    <section class="home-hero" data-reveal>
        <div class="site-container home-hero-grid">
            <div>
                <span class="site-eyebrow"><i data-lucide="heart"></i> VivaahFlow Weddings</span>
                <h1 class="home-title" data-hero-title>Crafting unforgettable celebrations</h1>
                <p class="home-lead" data-hero-lead>From the first enquiry to the final farewell, we bring venues, cuisine, decor and entertainment together into one seamless experience.</p>
                <div class="flex flex-wrap gap-3 mt-6">
                    <a class="btn btn-primary btn-lg" href="<?= url_for('contact') ?>">
                        <i data-lucide="calendar-heart"></i> Plan Your Event
                    </a>
                    <a class="btn btn-outline btn-lg" href="<?= url_for('services') ?>">
                        <i data-lucide="sparkles"></i> Browse Services
                    </a>
                </div>
                <div class="home-hero-points">
                    <span><i data-lucide="circle-check-big"></i> Verified specialists</span>
                    <span><i data-lucide="receipt"></i> Transparent quotations</span>
                    <span><i data-lucide="calendar-check"></i> End-to-end coordination</span>
                </div>
            </div>
            <aside class="home-hero-media" aria-label="Featured celebration">
                <div class="home-hero-frame" data-hero-media>
                    <img class="home-hero-mark" src="<?= asset_url('images/brand/logo.png') ?>" alt="<?= e($brandName) ?>" width="56" height="56">
                </div>
                <div class="home-hero-card" data-hero-rating hidden>
                    <span class="home-hero-stars" data-hero-stars></span>
                    <div>
                        <strong data-hero-rating-value></strong>
                        <span class="text-muted text-sm">average customer rating</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="site-section is-tight" data-reveal>
        <div class="site-container" data-stats hidden></div>
    </section>

    <section class="site-section" data-reveal>
        <div class="site-container">
            <div class="mb-6">
                <h2 class="site-section-title">Browse by category</h2>
                <p class="site-section-sub">Everything your celebration needs, neatly organised.</p>
            </div>
            <div class="home-category-grid" data-categories></div>
        </div>
    </section>

    <section class="site-section" style="background:#fff" data-reveal>
        <div class="site-container">
            <div class="flex items-end justify-between gap-4 flex-wrap mb-6">
                <div>
                    <h2 class="site-section-title">Featured services</h2>
                    <p class="site-section-sub">Hand-picked experiences our couples love.</p>
                </div>
                <a class="btn btn-soft btn-sm" href="<?= url_for('services') ?>">View all services</a>
            </div>
            <div class="site-grid" data-featured-services></div>
        </div>
    </section>

    <section class="site-section" data-featured-package-section hidden data-reveal>
        <div class="site-container home-feature-grid">
            <div class="home-feature-media" data-featured-package-media></div>
            <div>
                <span class="site-eyebrow">Featured package</span>
                <h2 class="site-section-title mt-3" data-featured-package-name></h2>
                <p class="site-section-sub" data-featured-package-description></p>
                <div class="home-feature-facts" data-featured-package-facts></div>
                <div class="flex flex-wrap gap-3 mt-6">
                    <a class="btn btn-primary" data-featured-package-link href="<?= url_for('packages') ?>">View package</a>
                    <a class="btn btn-outline" href="<?= url_for('contact') ?>">Ask about this package</a>
                </div>
            </div>
        </div>
    </section>

    <section class="site-section" style="background:#fff" data-gallery-section hidden data-reveal>
        <div class="site-container">
            <div class="flex items-end justify-between gap-4 flex-wrap mb-6">
                <div>
                    <h2 class="site-section-title">Recent celebrations</h2>
                    <p class="site-section-sub">A glimpse of the experiences we create.</p>
                </div>
                <a class="btn btn-soft btn-sm" href="<?= url_for('gallery') ?>">View gallery</a>
            </div>
            <div class="home-gallery-grid" data-gallery></div>
        </div>
    </section>

    <section class="site-section" data-featured-offer-section hidden data-reveal>
        <div class="site-container">
            <div class="home-offer">
                <div>
                    <span class="badge badge-success" data-featured-offer-label></span>
                    <h2 class="site-section-title mt-3" data-featured-offer-name></h2>
                    <p class="site-lead home-offer-text" data-featured-offer-description></p>
                    <p class="text-sm mt-3" data-featured-offer-validity></p>
                </div>
                <a class="btn btn-lg home-offer-cta" data-featured-offer-link href="<?= url_for('contact') ?>">Claim this offer</a>
            </div>
        </div>
    </section>

    <section class="site-section" style="background:#fff" data-reviews-section hidden data-reveal>
        <div class="site-container">
            <div class="mb-6">
                <h2 class="site-section-title">Loved by our couples</h2>
                <p class="site-section-sub">Real words from real celebrations.</p>
            </div>
            <div class="site-grid" data-reviews></div>
        </div>
    </section>

    <section class="site-section" data-reveal>
        <div class="site-container">
            <div class="site-cta">
                <h2 class="site-section-title">Ready to start planning?</h2>
                <p class="site-lead mt-2" style="max-width:520px;margin:8px auto 0">Tell us about your celebration and our team will curate a personalised proposal within 24 hours.</p>
                <div class="flex flex-wrap gap-3 justify-center mt-6">
                    <a class="btn btn-primary btn-lg" href="<?= url_for('contact') ?>">
                        <i data-lucide="send"></i> Request a Proposal
                    </a>
                    <a class="btn btn-outline btn-lg" href="<?= url_for('gallery') ?>">
                        <i data-lucide="images"></i> See Our Work
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
