(function () {
    'use strict';

    function src(item) {
        if (typeof item === 'string') {
            return item;
        }
        return item.image || item.url || item.path || '';
    }

    function label(item) {
        if (typeof item === 'string') {
            return '';
        }
        return item.title || item.caption || item.service_name || 'Wedding gallery photo';
    }

    function boot() {
        var node = UI.qs('[data-gallery]');
        if (!node) {
            return;
        }
        API.get('/public/gallery').then(function (data) {
            var items = data || [];
            if (items.length === 0) {
                node.innerHTML = '<div style="grid-column:1/-1">' + Site.empty('Our gallery is being curated', 'Photos from recent celebrations will appear here soon.') + '</div>';
                return;
            }
            node.innerHTML = items.map(function (item) {
                var source = src(item);
                return '<figure class="site-gallery-item">' +
                    (source ? '<img src="' + UI.escape(APP.linkUrl(source)) + '" alt="' + UI.escape(label(item)) + '" loading="lazy">' : UI.icon('image')) +
                    '</figure>';
            }).join('');
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function () {
            node.innerHTML = '<div style="grid-column:1/-1">' + Site.empty('Unable to load gallery', 'Please try again shortly.') + '</div>';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
