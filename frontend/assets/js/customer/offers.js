(function () {
    'use strict';

    function discountLabel(offer) {
        if (offer.discount_type === 'percent') {
            return Number(offer.discount_value) + '% off';
        }
        return Site.money(offer.discount_value) + ' off';
    }

    function applicableLabel(value) {
        if (value === 'all') {
            return 'All services & packages';
        }
        return APP.titleCase(value || 'services');
    }

    function boot() {
        var node = UI.qs('[data-offers]');
        if (!node) {
            return;
        }
        Loader.skeleton(node, { rows: 1, height: 240, full: true });
        API.get('/public/offers').then(function (data) {
            var today = new Date().toISOString().slice(0, 10);
            var offers = (data || []).filter(function (offer) {
                return offer.status === 'active' && (!offer.end_date || offer.end_date >= today);
            });
            if (offers.length === 0) {
                node.innerHTML = Site.empty('No active offers right now', 'Follow us or check back soon for seasonal savings.');
                return;
            }
            node.innerHTML = offers.map(function (offer) {
                return '' +
                    '<article class="site-card">' +
                        '<div class="site-media">' + UI.icon('badge-percent') + '<span class="badge badge-success" style="position:absolute;top:12px;left:12px">' + UI.escape(discountLabel(offer)) + '</span></div>' +
                        '<div class="site-card-body">' +
                            '<h3 class="text-lg font-semibold">' + UI.escape(offer.name) + '</h3>' +
                            '<p class="text-sm text-muted flex-1">' + UI.escape(offer.description || '') + '</p>' +
                            '<div class="text-xs text-muted">Applicable to: ' + UI.escape(applicableLabel(offer.applicable_to)) + '</div>' +
                            (offer.end_date ? '<div class="text-xs text-muted">Valid until ' + APP.formatDate(offer.end_date) + '</div>' : '') +
                            '<a class="btn btn-soft btn-sm mt-2" href="' + APP.route('contact') + APP.query({ offer: offer.slug }) + '">Claim offer</a>' +
                        '</div>' +
                    '</article>';
            }).join('');
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            node.innerHTML = Site.empty('Unable to load offers', error.message || 'Please try again.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
