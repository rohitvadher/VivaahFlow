(function () {
    'use strict';

    function initAccordion() {
        UI.qsa('[data-help-q]').forEach(function (heading) {
            heading.addEventListener('click', function () {
                var item = heading.closest('[data-help-item]');
                if (!item) {
                    return;
                }
                item.classList.toggle('is-open');
                if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
            });
        });
    }

    function letterCase(text) {
        return String(text || '').toLowerCase();
    }

    function applyFilter(query) {
        var needle = letterCase(query).trim();
        var visible = 0;
        UI.qsa('.help-card').forEach(function (card) {
            var matched = 0;
            UI.qsa('[data-help-item]', card).forEach(function (item) {
                var haystack = letterCase(item.textContent || '');
                var show = !needle || haystack.indexOf(needle) !== -1;
                item.style.display = show ? '' : 'none';
                if (show) {
                    matched += 1;
                    if (!needle) {
                        item.classList.remove('is-open');
                    } else {
                        item.classList.add('is-open');
                    }
                }
            });
            card.style.display = matched > 0 ? '' : 'none';
            visible += matched;
        });
        var count = UI.qs('[data-help-results]');
        if (count) {
            count.textContent = needle
                ? visible + ' result' + (visible === 1 ? '' : 's') + ' found'
                : '';
        }
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function initSearch() {
        var input = UI.qs('[data-help-search]');
        if (!input) {
            return;
        }
        input.addEventListener('input', window.APP.debounce(function () {
            applyFilter(input.value);
        }, 200));
    }

    function boot() {
        initAccordion();
        initSearch();
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();