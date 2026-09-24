<?php

declare(strict_types=1);

use App\Helpers\AssetManager;

$areaScripts = array_merge($areaScripts ?? [], ['js/customer/site.js']);
?>
    </div>
</main>
<footer class="site-footer">
    <div class="site-container">
        <div class="site-footer-bottom" style="margin-top:0;border-top:none">
            <span data-footer-text>&copy; <?= date('Y') ?> <?= e($brandName) ?>. All rights reserved.</span>
            <span><a href="<?= url_for('home') ?>">Back to website</a></span>
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