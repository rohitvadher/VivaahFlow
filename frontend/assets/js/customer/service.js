(function () {
    'use strict';

    var root = UI.qs('[data-service-detail]');
    if (!root) {
        return;
    }

    var slug = root.getAttribute('data-slug');

    function duration(minutes) {
        var value = Number(minutes);
        if (!value) {
            return '\u2014';
        }
        var hours = Math.floor(value / 60);
        var mins = value % 60;
        var parts = [];
        if (hours) {
            parts.push(hours + ' hr');
        }
        if (mins) {
            parts.push(mins + ' min');
        }
        return parts.join(' ');
    }

    function renderService(service) {
        var breadcrumb = UI.qs('[data-breadcrumb]');
        if (breadcrumb) {
            breadcrumb.textContent = service.name;
        }
        document.title = service.name + ' \u00b7 ' + (APP.siteSettings && APP.siteSettings.company_name ? APP.siteSettings.company_name : 'VivaahFlow');

        var gallery = (service.gallery || []);
        var reviews = (service.published_reviews || []);
        var similar = (service.similar || []);

        var html = '' +
            '<div class="grid gap-8 lg:grid-cols-3">' +
                '<div class="lg:col-span-2">' +
                    '<div class="site-media" style="border-radius:var(--radius);aspect-ratio:16/9;margin-bottom:20px">' + Site.image(service, 'sparkles') + '</div>' +
                    (service.category_name ? '<span class="badge badge-brand">' + UI.escape(service.category_name) + '</span>' : '') +
                    '<h1 class="site-section-title mt-3">' + UI.escape(service.name) + '</h1>' +
                    '<p class="text-muted mt-3 leading-7">' + UI.escape(service.description || service.short_description || '') + '</p>' +
                    (gallery.length > 0
                        ? '<h2 class="text-lg font-semibold mt-8 mb-3">Gallery</h2><div class="grid grid-cols-3 gap-3">' +
                            gallery.map(function (item) {
                                var src = typeof item === 'string' ? item : (item.image || item.url || '');
                                return '<div class="site-gallery-item">' + (src ? '<img src="' + UI.escape(APP.linkUrl(src)) + '" alt="" loading="lazy">' : UI.icon('image')) + '</div>';
                            }).join('') + '</div>'
                        : '') +
                    (reviews.length > 0
                        ? '<h2 class="text-lg font-semibold mt-8 mb-3">Customer reviews</h2><div class="grid gap-4 md:grid-cols-2">' +
                            reviews.map(function (review) {
                                return '<article class="card card-pad"><div class="flex gap-1 text-amber-400 mb-2">' + Site.ratingStars(review.rating) + '</div>' +
                                    (review.title ? '<h3 class="font-semibold">' + UI.escape(review.title) + '</h3>' : '') +
                                    '<p class="text-sm text-muted mt-2">' + UI.escape(review.comment || '') + '</p>' +
                                    '<div class="text-xs text-muted mt-3">' + UI.escape(review.customer_name || 'Verified customer') + '</div></article>';
                            }).join('') + '</div>'
                        : '') +
                '</div>' +
                '<aside>' +
                    '<div class="card card-pad" style="position:sticky;top:90px">' +
                        '<div class="text-sm text-muted">Starting from</div>' +
                        '<div class="site-price" style="font-size:28px">' + Site.money(service.starting_price) + '</div>' +
                        '<div class="detail-list mt-4">' +
                            '<div class="detail-row"><span>Duration</span><strong>' + duration(service.duration_minutes) + '</strong></div>' +
                            (service.category_name ? '<div class="detail-row"><span>Category</span><strong>' + UI.escape(service.category_name) + '</strong></div>' : '') +
                        '</div>' +
                        '<a class="btn btn-primary btn-block mt-4" href="' + APP.route('contact') + APP.query({ service: service.slug }) + '">' +
                            UI.icon('send') + ' Enquire Now</a>' +
                    '</div>' +
                '</aside>' +
            '</div>' +
            (similar.length > 0
                ? '<section class="mt-12"><h2 class="site-section-title mb-4">You may also like</h2><div class="site-grid">' +
                    similar.map(Site.serviceCard).join('') + '</div></section>'
                : '');
        root.innerHTML = html;
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function load() {
        if (!slug) {
            root.innerHTML = Site.empty('Service not found', 'The link you followed may be broken.');
            return;
        }
        API.get('/public/services/' + encodeURIComponent(slug)).then(function (data) {
            renderService(data || {});
        }).catch(function (error) {
            root.innerHTML = Site.empty('Service not found', error.message || 'This service may no longer be available.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
