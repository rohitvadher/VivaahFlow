(function () {
    'use strict';

    function money(value) {
        var amount = Number(value);
        if (!isFinite(amount)) {
            return '\u2014';
        }
        return '\u20b9' + new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 }).format(amount);
    }

    function image(record, iconName) {
        var source = (record && (record.image_url || record.cover_image_url || record.image || record.cover_image)) || '';
        if (source) {
            return '<img src="' + UI.escape(APP.linkUrl(source)) + '" alt="' + UI.escape((record && record.name) || '') + '" loading="lazy">';
        }
        return UI.icon(iconName || 'image');
    }

    function ratingStars(rating) {
        var value = Math.max(0, Math.min(5, Math.round(Number(rating) || 0)));
        var html = '';
        for (var i = 1; i <= 5; i += 1) {
            html += '<i data-lucide="star" class="' + (i <= value ? 'text-amber-400' : 'text-slate-300') + '" style="fill:currentColor"></i>';
        }
        return html;
    }

    function serviceCard(service) {
        var href = APP.route('service', { slug: service.slug });
        return '' +
            '<article class="site-card">' +
                '<a class="site-media" href="' + href + '">' + image(service, 'sparkles') + '</a>' +
                '<div class="site-card-body">' +
                    (service.category_name ? '<span class="text-xs font-semibold uppercase tracking-wide" style="color:var(--brand-500)">' + UI.escape(service.category_name) + '</span>' : '') +
                    '<a href="' + href + '" class="text-lg font-semibold hover:underline">' + UI.escape(service.name) + '</a>' +
                    '<p class="text-sm text-muted flex-1">' + UI.escape(service.short_description || '') + '</p>' +
                    '<div class="flex items-center justify-between mt-2">' +
                        '<span class="site-price">From ' + money(service.starting_price) + '</span>' +
                        '<a class="btn btn-soft btn-sm" href="' + href + '">View</a>' +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function packageCard(pkg) {
        var href = APP.route('package', { slug: pkg.slug });
        return '' +
            '<article class="site-card">' +
                '<a class="site-media" href="' + href + '">' + image(pkg, 'gift') + '</a>' +
                '<div class="site-card-body">' +
                    '<a href="' + href + '" class="text-lg font-semibold hover:underline">' + UI.escape(pkg.name) + '</a>' +
                    '<p class="text-sm text-muted flex-1">' + UI.escape(pkg.description || '') + '</p>' +
                    '<div class="flex flex-wrap gap-2 text-xs text-muted">' +
                        '<span class="badge badge-plain">' + Number(pkg.service_count || (pkg.services ? pkg.services.length : 0)) + ' services</span>' +
                        (pkg.discount_label ? '<span class="badge badge-success">' + UI.escape(pkg.discount_label) + '</span>' : '') +
                    '</div>' +
                    '<div class="flex items-center justify-between mt-2">' +
                        '<span class="site-price">' + money(pkg.total_amount) + '</span>' +
                        '<a class="btn btn-soft btn-sm" href="' + href + '">View</a>' +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function empty(message, hint) {
        return '' +
            '<div class="card"><div class="table-empty">' +
                '<div class="empty-title">' + UI.escape(message) + '</div>' +
                (hint ? '<p class="text-sm text-muted mt-1">' + UI.escape(hint) + '</p>' : '') +
            '</div></div>';
    }

    function clearNode(node) {
        while (node.firstChild) {
            node.removeChild(node.firstChild);
        }
    }

    function renderSettings(settings) {
        settings = settings || {};
        APP.siteSettings = settings;
        UI.qsa('[data-brand-name], [data-brand-name-footer]').forEach(function (node) {
            if (settings.company_name) {
                node.textContent = settings.company_name;
            }
        });
        UI.qsa('[data-footer-tagline]').forEach(function (node) {
            if (settings.tagline) {
                node.textContent = settings.tagline;
            }
        });
        UI.qsa('[data-footer-address]').forEach(function (node) {
            node.textContent = settings.company_address || '';
        });
        UI.qsa('[data-footer-email]').forEach(function (node) {
            node.textContent = settings.company_email || '';
        });
        UI.qsa('[data-footer-phone]').forEach(function (node) {
            node.textContent = settings.company_phone || '';
        });
        UI.qsa('[data-footer-text]').forEach(function (node) {
            node.textContent = settings.footer_text || ('\u00a9 ' + new Date().getFullYear() + ' ' + (settings.company_name || 'VivaahFlow') + '. All rights reserved.');
        });
        var hasContact = !!(settings.company_address || settings.company_email || settings.company_phone);
        UI.qsa('[data-footer-contact-empty]').forEach(function (node) {
            node.hidden = hasContact;
        });
    }

    function initNav() {
        var toggle = document.querySelector('[data-site-nav-toggle]');
        var nav = document.querySelector('[data-site-nav]');
        var header = document.querySelector('.site-header');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                nav.classList.toggle('is-open');
            });
        }
        if (header) {
            var onScroll = function () {
                header.classList.toggle('is-scrolled', window.scrollY > 8);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }
    }

    function initLogout() {
        UI.qsa('[data-logout]').forEach(function (button) {
            button.addEventListener('click', function () {
                API.logout().then(function () {
                    window.location.href = APP.route('home');
                }).catch(function () {
                    window.location.href = APP.route('home');
                });
            });
        });
    }

    var settingsPromise = null;

    function fetchSettings() {
        if (!settingsPromise) {
            settingsPromise = API.get('/public/bootstrap').then(function (data) {
                var settings = data ? data.settings : null;
                renderSettings(settings);
                return settings;
            }).catch(function () {
                renderSettings(null);
                return null;
            });
        }
        return settingsPromise;
    }

    function boot() {
        initNav();
        initLogout();
        if (window.APP && APP.siteSettings) {
            renderSettings(APP.siteSettings);
            return;
        }
        fetchSettings();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.Site = {
        money: money,
        image: image,
        ratingStars: ratingStars,
        serviceCard: serviceCard,
        packageCard: packageCard,
        empty: empty,
        clearNode: clearNode,
        settings: fetchSettings
    };
})();
