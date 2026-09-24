(function () {
    'use strict';

    function renderSummary(data) {
        var node = UI.qs('[data-review-summary]');
        if (!node) {
            return;
        }
        var total = Number(data.total || 0);
        if (total === 0) {
            node.innerHTML = '<div class="table-empty"><div class="empty-title">No reviews yet</div><p class="text-sm text-muted mt-1">Be the first to share your experience.</p></div>';
            return;
        }
        var counts = {};
        (data.distribution || []).forEach(function (row) {
            counts[Number(row.rating)] = Number(row.total);
        });
        var bars = '';
        for (var star = 5; star >= 1; star -= 1) {
            var count = counts[star] || 0;
            var percent = total > 0 ? Math.round((count / total) * 100) : 0;
            bars += '' +
                '<div class="flex items-center gap-2 text-xs text-muted">' +
                    '<span style="width:34px">' + star + ' star</span>' +
                    '<span style="flex:1;height:8px;border-radius:999px;background:var(--ink-100);overflow:hidden">' +
                        '<span style="display:block;height:100%;width:' + percent + '%;background:var(--brand-500)"></span>' +
                    '</span>' +
                    '<span style="width:26px;text-align:right">' + count + '</span>' +
                '</div>';
        }
        node.innerHTML = '' +
            '<div class="text-center">' +
                '<div class="site-price" style="font-size:40px">' + Number(data.average || 0).toFixed(1) + '</div>' +
                '<div class="flex justify-center gap-1 text-amber-400 my-2">' + Site.ratingStars(data.average) + '</div>' +
                '<div class="text-sm text-muted">Based on ' + total + ' review' + (total === 1 ? '' : 's') + '</div>' +
            '</div>' +
            '<div class="mt-4 grid gap-2">' + bars + '</div>';
    }

    function renderReviews(reviews) {
        var node = UI.qs('[data-reviews]');
        if (!node) {
            return;
        }
        if (!reviews || reviews.length === 0) {
            node.innerHTML = Site.empty('No reviews yet', 'Approved customer reviews will appear here.');
            return;
        }
        node.innerHTML = reviews.map(function (review) {
            return '' +
                '<article class="card card-pad">' +
                    '<div class="flex items-start justify-between gap-3 flex-wrap">' +
                        '<div>' +
                            '<div class="flex gap-1 text-amber-400 mb-1">' + Site.ratingStars(review.rating) + '</div>' +
                            (review.title ? '<h3 class="font-semibold">' + UI.escape(review.title) + '</h3>' : '') +
                        '</div>' +
                        '<span class="text-xs text-muted">' + APP.formatDate(review.created_at) + '</span>' +
                    '</div>' +
                    '<p class="text-sm text-muted mt-2">' + UI.escape(review.comment || '') + '</p>' +
                    '<div class="text-xs text-muted mt-3">' + UI.escape(review.customer_name || 'Verified customer') +
                        (review.service_name ? ' \u00b7 ' + UI.escape(review.service_name) : '') + '</div>' +
                '</article>';
        }).join('');
    }

    function boot() {
        API.get('/public/reviews').then(function (data) {
            data = data || {};
            renderSummary(data);
            renderReviews(data.reviews || []);
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            var node = UI.qs('[data-review-summary]');
            if (node) {
                node.innerHTML = '<div class="table-empty"><div class="empty-title">Unable to load reviews</div><p class="text-sm text-muted mt-1">' + UI.escape(error.message || 'Please try again.') + '</p></div>';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
