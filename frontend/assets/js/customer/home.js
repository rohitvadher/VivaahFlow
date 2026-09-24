(function () {
    'use strict';

    var categoryIcons = {
        'wedding-venues': 'map-pin',
        'catering-cuisine': 'utensils',
        'photography-videography': 'camera',
        'decoration-florals': 'flower-2',
        'music-entertainment': 'music',
        'beauty-styling': 'sparkles'
    };

    function renderStats(stats) {
        var node = UI.qs('[data-stats]');
        if (!node || !stats) {
            return;
        }
        var cards = [
            { label: 'Services', value: Number(stats.services || 0), icon: 'sparkles' },
            { label: 'Packages', value: Number(stats.packages || 0), icon: 'gift' },
            { label: 'Happy couples', value: Number(stats.happy_customers || 0), icon: 'heart-handshake' },
            { label: 'Average rating', value: Number(stats.avg_rating || 0), icon: 'star', suffix: ' / 5' }
        ].filter(function (card) {
            return card.value > 0;
        });
        if (cards.length === 0) {
            node.hidden = true;
            return;
        }
        node.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">' + cards.map(function (card) {
            return '' +
                '<div class="card card-pad flex items-center gap-3">' +
                    '<span class="stat-icon">' + UI.icon(card.icon) + '</span>' +
                    '<div>' +
                        '<div class="stat-value">' + card.value + (card.suffix || '') + '</div>' +
                        '<div class="stat-label">' + UI.escape(card.label) + '</div>' +
                    '</div>' +
                '</div>';
        }).join('') + '</div>';
        node.hidden = false;
    }

    function renderHeroMedia(services, gallery, stats) {
        var frame = UI.qs('[data-hero-media]');
        var rating = UI.qs('[data-hero-rating]');
        var featured = (services || []).filter(function (service) {
            return service.image_url || service.image;
        })[0] || {};
        var galleryImage = ((gallery || [])[0] || {}).image || '';
        var source = featured.image_url || featured.image || galleryImage;
        if (frame && source) {
            frame.innerHTML = '<img src="' + UI.escape(APP.linkUrl(source)) + '" alt="Celebration arranged by VivaahFlow" loading="lazy">';
        }
        var average = Number((stats || {}).avg_rating || 0);
        if (rating && average > 0) {
            UI.qs('[data-hero-rating-value]', rating).textContent = average.toFixed(1) + ' / 5';
            UI.qs('[data-hero-stars]', rating).innerHTML = Site.ratingStars(average);
            rating.hidden = false;
        }
    }

    function renderCategories(categories, services) {
        var node = UI.qs('[data-categories]');
        if (!node) {
            return;
        }
        if (!categories || categories.length === 0) {
            node.innerHTML = Site.empty('Categories are being updated', 'Please check back shortly.');
            return;
        }
        node.innerHTML = categories.map(function (category) {
            var count = (services || []).filter(function (service) {
                return Number(service.category_id) === Number(category.id);
            }).length;
            return '' +
                '<a class="home-category-card" href="' + APP.route('services') + APP.query({ category: category.slug }) + '">' +
                    '<span class="home-category-icon">' + UI.icon(categoryIcons[category.slug] || 'sparkles') + '</span>' +
                    '<span>' +
                        '<span class="block font-semibold">' + UI.escape(category.name) + '</span>' +
                        '<span class="block text-sm text-muted mt-1">' + UI.escape(category.description || (count + (count === 1 ? ' service' : ' services'))) + '</span>' +
                    '</span>' +
                '</a>';
        }).join('');
    }

    function renderServices(services) {
        var node = UI.qs('[data-featured-services]');
        if (!node) {
            return;
        }
        var featured = (services || []).filter(function (service) {
            return Number(service.is_featured) === 1;
        });
        if (featured.length === 0) {
            featured = (services || []).slice(0, 6);
        }
        featured = featured.slice(0, 6);
        node.innerHTML = featured.length > 0
            ? featured.map(Site.serviceCard).join('')
            : Site.empty('Services are being updated', 'Please check back shortly.');
    }

    function renderFeaturedPackage(packages) {
        var section = UI.qs('[data-featured-package-section]');
        if (!section) {
            return;
        }
        var featured = (packages || []).filter(function (pkg) {
            return Number(pkg.is_featured) === 1;
        })[0] || (packages || [])[0];
        if (!featured) {
            section.hidden = true;
            return;
        }
        UI.qs('[data-featured-package-media]').innerHTML = Site.image(featured, 'gift');
        UI.qs('[data-featured-package-name]').textContent = featured.name || 'Curated package';
        UI.qs('[data-featured-package-description]').textContent = featured.description || 'A carefully bundled celebration package.';
        UI.qs('[data-featured-package-facts]').innerHTML =
            '<span class="badge badge-plain">' + Number(featured.service_count || (featured.services || []).length || 0) + ' services</span>' +
            (Number(featured.discount_amount) > 0 && featured.discount_label
                ? '<span class="badge badge-success">' + UI.escape(featured.discount_label) + '</span>'
                : '') +
            '<span class="site-price">' + Site.money(featured.total_amount) + '</span>';
        UI.qs('[data-featured-package-link]').href = APP.route('package', { slug: featured.slug });
        section.hidden = false;
    }

    function renderGallery(gallery) {
        var section = UI.qs('[data-gallery-section]');
        var node = UI.qs('[data-gallery]');
        if (!section || !node) {
            return;
        }
        var items = (gallery || []).slice(0, 6);
        if (items.length === 0) {
            section.hidden = true;
            return;
        }
        node.innerHTML = items.map(function (item) {
            var source = item.image || item.url || item.path || '';
            return '<figure class="site-gallery-item">' +
                (source ? '<img src="' + UI.escape(APP.linkUrl(source)) + '" alt="' + UI.escape(item.caption || item.service_name || '') + '" loading="lazy">' : UI.icon('image')) +
                '</figure>';
        }).join('');
        section.hidden = false;
    }

    function renderFeaturedOffer(offers) {
        var section = UI.qs('[data-featured-offer-section]');
        if (!section) {
            return;
        }
        var today = new Date().toISOString().slice(0, 10);
        var offer = (offers || []).filter(function (item) {
            return !item.end_date || item.end_date >= today;
        })[0];
        if (!offer) {
            section.hidden = true;
            return;
        }
        var label = offer.discount_label || (offer.discount_type === 'percent'
            ? Number(offer.discount_value) + '% off'
            : Site.money(offer.discount_value) + ' off');
        UI.qs('[data-featured-offer-label]').textContent = label;
        UI.qs('[data-featured-offer-name]').textContent = offer.name || 'Limited-time offer';
        UI.qs('[data-featured-offer-description]').textContent = offer.description || 'Ask our team how this saving applies to your celebration.';
        UI.qs('[data-featured-offer-validity]').textContent = offer.end_date ? 'Valid until ' + APP.formatDate(offer.end_date) : '';
        UI.qs('[data-featured-offer-link]').href = APP.route('contact') + APP.query({ offer: offer.slug });
        section.hidden = false;
    }

    function renderReviews(reviews) {
        var section = UI.qs('[data-reviews-section]');
        var node = UI.qs('[data-reviews]');
        if (!section || !node) {
            return;
        }
        var list = (reviews || []).slice(0, 3);
        if (list.length === 0) {
            section.hidden = true;
            return;
        }
        node.innerHTML = list.map(function (review) {
            return '' +
                '<article class="card card-pad">' +
                    '<div class="flex gap-1 text-amber-400 mb-2">' + Site.ratingStars(review.rating) + '</div>' +
                    (review.title ? '<h3 class="font-semibold">' + UI.escape(review.title) + '</h3>' : '') +
                    '<p class="text-sm text-muted mt-2 flex-1">' + UI.escape(review.comment || '') + '</p>' +
                    '<div class="text-xs text-muted mt-3">' + UI.escape(review.customer_name || 'Verified customer') +
                        (review.service_name ? ' \u00b7 ' + UI.escape(review.service_name) : '') + '</div>' +
                '</article>';
        }).join('');
        section.hidden = false;
    }

    function reveal() {
        var nodes = UI.qsa('[data-reveal]');
        if (!nodes.length) {
            return;
        }
        if (!('IntersectionObserver' in window)) {
            nodes.forEach(function (node) { node.classList.add('is-visible'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        nodes.forEach(function (node) { observer.observe(node); });
    }

    function boot() {
        reveal();
        API.get('/public/home').then(function (data) {
            data = data || {};
            if (data.settings) {
                var title = UI.qs('[data-hero-title]');
                if (title && data.settings.tagline) {
                    title.textContent = data.settings.tagline;
                }
            }
            renderStats(data.stats);
            renderHeroMedia(data.services, data.gallery, data.stats);
            renderCategories(data.categories, data.services);
            renderServices(data.services);
            renderFeaturedPackage(data.packages);
            renderGallery(data.gallery);
            renderFeaturedOffer(data.offers);
            renderReviews(data.reviews);
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            Alerts.error(error.message || 'Unable to load the homepage right now.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
