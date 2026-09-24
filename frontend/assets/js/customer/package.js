(function () {
    'use strict';

    var root = UI.qs('[data-package-detail]');
    if (!root) {
        return;
    }

    var slug = root.getAttribute('data-slug');

    function render(pkg) {
        var services = pkg.services || [];
        var breadcrumb = UI.qs('[data-breadcrumb]');
        if (breadcrumb) {
            breadcrumb.textContent = pkg.name;
        }
        var discount = Number(pkg.discount_amount);

        var html = '' +
            '<div class="grid gap-8 lg:grid-cols-3">' +
                '<div class="lg:col-span-2">' +
                    '<div class="site-media" style="border-radius:var(--radius);aspect-ratio:16/9;margin-bottom:20px">' + Site.image(pkg, 'gift') + '</div>' +
                    '<h1 class="site-section-title">' + UI.escape(pkg.name) + '</h1>' +
                    '<p class="text-muted mt-3 leading-7">' + UI.escape(pkg.description || '') + '</p>' +
                    (services.length > 0
                        ? '<h2 class="text-lg font-semibold mt-8 mb-3">What\'s included</h2><div class="card">' +
                            services.map(function (service) {
                                return '<div class="item-row flex items-center gap-3">' +
                                    '<span class="badge badge-plain">' + Number(service.quantity || 1) + '\u00d7</span>' +
                                    '<div class="flex-1 min-w-0">' +
                                        '<div class="font-medium">' + UI.escape(service.name) + '</div>' +
                                        (service.category_name ? '<div class="text-xs text-muted">' + UI.escape(service.category_name) + '</div>' : '') +
                                    '</div>' +
                                    '<span class="site-price">' + Site.money(service.starting_price) + '</span>' +
                                '</div>';
                            }).join('') + '</div>'
                        : '') +
                '</div>' +
                '<aside>' +
                    '<div class="card card-pad" style="position:sticky;top:90px">' +
                        '<div class="totals-list">' +
                            '<div class="totals-row"><span>Services subtotal</span><strong>' + Site.money(pkg.base_amount) + '</strong></div>' +
                            (discount > 0 ? '<div class="totals-row"><span>Discount' + (pkg.discount_label ? ' (' + UI.escape(pkg.discount_label) + ')' : '') + '</span><strong style="color:var(--success)">\u2212 ' + Site.money(pkg.discount_amount) + '</strong></div>' : '') +
                            '<div class="totals-row is-total"><span>Package price</span><strong>' + Site.money(pkg.total_amount) + '</strong></div>' +
                        '</div>' +
                        '<a class="btn btn-primary btn-block mt-4" href="' + APP.route('contact') + APP.query({ package: pkg.slug }) + '">' +
                            UI.icon('send') + ' Enquire About Package</a>' +
                        '<p class="text-xs text-muted mt-3">Final pricing is confirmed in your personalised quotation.</p>' +
                    '</div>' +
                '</aside>' +
            '</div>';
        root.innerHTML = html;
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function load() {
        if (!slug) {
            root.innerHTML = Site.empty('Package not found', 'The link you followed may be broken.');
            return;
        }
        API.get('/public/packages/' + encodeURIComponent(slug)).then(function (data) {
            render(data || {});
        }).catch(function (error) {
            root.innerHTML = Site.empty('Package not found', error.message || 'This package may no longer be available.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
