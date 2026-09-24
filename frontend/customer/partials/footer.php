<?php

declare(strict_types=1);

use App\Helpers\AssetManager;

$areaScripts = array_merge($areaScripts ?? [], ['js/customer/site.js']);
?>
</main>
<footer class="site-footer">
    <div class="site-container">
        <div class="grid gap-8 md:grid-cols-4">
            <div>
                <h4 data-brand-name-footer><?= e($brandName) ?></h4>
                <p class="text-sm leading-6" data-footer-tagline>Weddings planned with heart, executed with precision.</p>
            </div>
            <div>
                <h4>Explore</h4>
                <a href="<?= url_for('services') ?>">Services</a>
                <a href="<?= url_for('packages') ?>">Packages</a>
                <a href="<?= url_for('gallery') ?>">Gallery</a>
                <a href="<?= url_for('offers') ?>">Offers</a>
            </div>
            <div>
                <h4>Account</h4>
                <a href="<?= url_for('contact') ?>">Plan Your Event</a>
                <?php if (site_is_customer()): ?>
                    <a href="<?= e(url_for('account.dashboard')) ?>">My Portal</a>
                <?php else: ?>
                    <a href="<?= e(url_for('login')) ?>">Sign In</a>
                    <a href="<?= url_for('register') ?>">Create Account</a>
                <?php endif; ?>
            </div>
            <div>
                <h4>Contact</h4>
                <p class="text-sm" data-footer-address></p>
                <p class="text-sm mt-2" data-footer-email></p>
                <p class="text-sm" data-footer-phone></p>
                <p class="text-sm mt-2" data-footer-contact-empty hidden>Prefer to talk first? <a href="<?= url_for('contact') ?>" style="color:#fff;text-decoration:underline">Send us an enquiry</a>.</p>
            </div>
        </div>
        <div class="site-footer-bottom">
            <span data-footer-text>&copy; <?= date('Y') ?> <?= e($brandName) ?>. All rights reserved.</span>
            <span>Built for unforgettable celebrations.</span>
        </div>
    </div>
</footer>
<?= AssetManager::scripts($pageScript ?? '', $extraLibraries ?? [], $areaScripts) ?>
<script>
    window.APP.loginUrl = <?= json_encode(url_for('login')) ?>;
    window.APP.portalUrl = <?= json_encode(url_for('account.dashboard')) ?>;
</script>
</body>
</html>