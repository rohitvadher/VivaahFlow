(function () {
    'use strict';

    function create(config) {
        var tableEl = typeof config.table === 'string' ? document.getElementById(config.table) : config.table;
        var paginationEl = typeof config.pagination === 'string' ? document.getElementById(config.pagination) : config.pagination;
        var searchEl = config.search ? (typeof config.search === 'string' ? document.querySelector(config.search) : config.search) : null;
        var statusEl = config.status ? (typeof config.status === 'string' ? document.querySelector(config.status) : config.status) : null;
        var filterEls = config.filters || [];
        var columns = config.columns || [];
        var perPage = config.perPage || 10;
        var endpoint = config.endpoint;
        var state = { page: 1, search: '', status: config.statusDefault || '' };
        var bound = false;

        function resolveEl(ref) {
            return typeof ref === 'string' ? document.querySelector(ref) : ref;
        }

        function rowsHtml(items) {
            if (!items || !items.length) {
                if (window.Loader && window.Loader.emptyHtml) {
                    return window.Loader.emptyHtml({
                        title: config.emptyText,
                        hint: config.emptyHint
                    });
                }
                return '<div class="table-empty">' +
                    (window.UI.icon('inbox', '')) +
                    '<div class="empty-title">' + window.UI.escape(config.emptyText || 'No records found') + '</div>' +
                    '<div class="text-sm mt-1">' + window.UI.escape(config.emptyHint || 'Try adjusting your search or filters.') + '</div>' +
                    '</div>';
            }
            var head = '<tr>' + columns.map(function (column) {
                return '<th' + (column.className ? ' class="' + column.className + '"' : '') + '>' + window.UI.escape(column.label || '') + '</th>';
            }).join('');
            if (config.rowActions) {
                head += '<th style="text-align:right">Actions</th>';
            }
            head += '</tr>';

            var body = items.map(function (row) {
                var cells = columns.map(function (column) {
                    var value = column.render ? column.render(row) : window.UI.escape(row[column.key]);
                    return '<td' + (column.className ? ' class="' + column.className + '"' : '') + '>' + value + '</td>';
                }).join('');
                if (config.rowActions) {
                    cells += '<td><div class="cell-actions">' + config.rowActions(row) + '</div></td>';
                }
                return '<tr data-row-id="' + window.UI.escape(row.id) + '">' + cells + '</tr>';
            }).join('');

            return '<div class="table-wrap"><table class="table"><thead>' + head + '</thead><tbody>' + body + '</tbody></table></div>';
        }

        function loadingHtml() {
            if (window.Loader && window.Loader.skeletonHtml) {
                return window.Loader.skeletonHtml({ rows: 5 });
            }
            var bars = '';
            for (var i = 0; i < 5; i++) {
                bars += '<div class="skeleton skeleton-row"></div>';
            }
            return '<div style="padding:20px">' + bars + '</div>';
        }

        function load() {
            if (!tableEl) {
                return Promise.resolve();
            }
            tableEl.innerHTML = loadingHtml();
            var params = Object.assign({ page: state.page, per_page: perPage }, config.query || {});
            if (state.search) {
                params.search = state.search;
            }
            if (state.status) {
                params.status = state.status;
            }
            filterEls.forEach(function (filter) {
                var el = resolveEl(typeof filter === 'string' ? filter : filter.el);
                var key = filter.key || (el && el.getAttribute && el.getAttribute('data-filter-key'));
                if (el && key && el.value) {
                    params[key] = el.value;
                }
            });

            return API.get(endpoint, params).then(function (data) {
                var items = data && data.items ? data.items : (Array.isArray(data) ? data : []);
                if (config.transform) {
                    items = items.map(config.transform);
                }
                tableEl.innerHTML = rowsHtml(items);
                if (window.UI.applyPermissions) {
                    window.UI.applyPermissions(tableEl);
                    if (!window.APP.currentUser) {
                        document.addEventListener('app:ready', function () {
                            window.UI.applyPermissions(tableEl);
                        }, { once: true });
                    }
                }
                if (paginationEl && data && data.pagination) {
                    window.UI.renderPagination(paginationEl, data.pagination, function (page) {
                        state.page = page;
                        load();
                    });
                } else if (paginationEl) {
                    paginationEl.innerHTML = '';
                }
                if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
                if (typeof config.onLoaded === 'function') {
                    config.onLoaded(items, data);
                }
            }).catch(function (error) {
                tableEl.innerHTML = '<div class="table-empty"><div class="empty-title">' + window.UI.escape(error.message) + '</div></div>';
            });
        }

        function bind() {
            if (bound) {
                return;
            }
            bound = true;
            if (searchEl) {
                searchEl.addEventListener('input', window.APP.debounce(function () {
                    state.search = searchEl.value.trim();
                    state.page = 1;
                    load();
                }, 350));
            }
            if (statusEl) {
                statusEl.addEventListener('change', function () {
                    state.status = statusEl.value;
                    state.page = 1;
                    load();
                });
            }
            filterEls.forEach(function (filter) {
                var el = resolveEl(typeof filter === 'string' ? filter : filter.el);
                if (el && typeof el.addEventListener === 'function') {
                    el.addEventListener('change', function () {
                        state.page = 1;
                        load();
                    });
                }
            });
        }

        bind();
        load();

        return {
            reload: function () {
                load();
            },
            reset: function () {
                state.page = 1;
                state.search = '';
                if (searchEl) {
                    searchEl.value = '';
                }
                load();
            },
            state: state
        };
    }

    window.Tables = { create: create };
})();
