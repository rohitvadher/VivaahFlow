<?php

declare(strict_types=1);

use App\Helpers\AssetManager;
?>
        </main>
    </div>
</div>
<?= AssetManager::scripts($pageScript ?? '', $extraLibraries ?? [], $areaScripts ?? []) ?>
<script>
    (function () {
        'use strict';

        window.APP.loginUrl = '<?= url_for('manage.login') ?>';

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-logout]');
            if (!trigger) {
                return;
            }
            event.preventDefault();
            Alerts.confirm({
                title: 'Sign out?',
                text: 'You will need to sign in again to access the panel.',
                confirmText: 'Sign Out',
                danger: true
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.logout().then(function () {
                    window.location.href = window.APP.loginUrl;
                }).catch(function () {
                    window.location.href = window.APP.loginUrl;
                });
            });
        });

        API.me().then(function (data) {
            window.APP.currentUser = data ? data.user : null;
            UI.applyPermissions(document);
            document.dispatchEvent(new CustomEvent('app:ready', { detail: window.APP.currentUser }));
        }).catch(function () {
            /* handled by API 401 redirect */
        });

        API.get('/notifications').then(function (data) {
            var items = (data && data.items) || [];
            var unread = items.filter(function (item) { return Number(item.is_read) === 0; }).length;
            var dot = document.querySelector('[data-notification-dot]');
            if (dot && unread > 0) {
                dot.classList.remove('hidden');
            }
        }).catch(function () {
            return;
        });
    })();
</script>
</body>
</html>