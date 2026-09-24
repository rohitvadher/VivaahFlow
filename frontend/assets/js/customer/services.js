(function () {
    'use strict';

    var state = {
        search: '',
        categoryId: null,
        categories: []
    };

    var categorySlug = new URLSearchParams(window.location.search).get('category');

    function renderCategories() {
        var node = UI.qs('[data-categories]');
        if (!node) {
            return;
        }
        var items = '<button type="button" class="site-chip' + (state.categoryId === null ? ' is-active' : '') + '" data-category="">All services</button>';
        items += state.categories.map(function (category) {
            var active = Number(state.categoryId) === Number(category.id);
            return '<button type="button" class="site-chip' + (active ? ' is-active' : '') + '" data-category="' + category.id + '">' + UI.escape(category.name) + '</button>';
        }).join('');
        node.innerHTML = items;
        UI.qsa('[data-category]', node).forEach(function (button) {
            button.addEventListener('click', function () {
                state.categoryId = button.getAttribute('data-category') === '' ? null : Number(button.getAttribute('data-category'));
                renderCategories();
                load();
            });
        });
    }

    function load() {
        var node = UI.qs('[data-services]');
        if (node) {
            Loader.skeleton(node, { rows: 2, height: 280, full: true });
        }
        API.get('/public/services' + APP.query({ search: state.search, category_id: state.categoryId })).then(function (data) {
            data = data || {};
            state.categories = data.categories || [];
            if (categorySlug && state.categoryId === null) {
                var match = state.categories.filter(function (category) {
                    return category.slug === categorySlug;
                })[0];
                if (match) {
                    state.categoryId = Number(match.id);
                }
                categorySlug = null;
            }
            renderCategories();
            var services = data.services || [];
            node.innerHTML = services.length > 0
                ? services.map(Site.serviceCard).join('')
                : Site.empty('No services match your search', 'Try a different keyword or category.');
            if (window.Icons && typeof window.Icons.refresh === 'function') {
                window.Icons.refresh(node);
            } else if (window.lucide) {
                try { window.lucide.createIcons(); } catch (error) { /* ignore */ }
            }
        }).catch(function (error) {
            Alerts.error(error.message || 'Unable to load services.');
        });
    }

    function init() {
        var search = UI.qs('[data-service-search]');
        if (search) {
            search.addEventListener('input', APP.debounce(function () {
                state.search = search.value.trim();
                load();
            }, 350));
        }
        load();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
