(function () {
    'use strict';

    function boot() {
        var node = UI.qs('[data-packages]');
        if (!node) {
            return;
        }
        Loader.skeleton(node, { rows: 2, height: 280, full: true });
        API.get('/public/packages').then(function (data) {
            var packages = data || [];
            node.innerHTML = packages.length > 0
                ? packages.map(Site.packageCard).join('')
                : Site.empty('No packages available', 'New packages are added regularly.');
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            node.innerHTML = Site.empty('Unable to load packages', error.message || 'Please try again.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
