(function () {
    'use strict';

    var tableEl = document.getElementById('activity-table');

    function actionBadge(action) {
        var prefix = String(action || '').split('.')[0];
        var map = {
            create: 'badge-success',
            created: 'badge-success',
            update: 'badge-info',
            updated: 'badge-info',
            status: 'badge-warning',
            delete: 'badge-danger',
            deleted: 'badge-danger',
            accepted: 'badge-success',
            rejected: 'badge-danger',
            login: 'badge-muted'
        };
        return '<span class="badge ' + (map[prefix] || 'badge-plain') + ' badge-plain">' + UI.escape(prefix) + '</span>';
    }

    window.Tables.create({
        table: tableEl,
        pagination: 'activity-pagination',
        search: '[data-table-search]',
        endpoint: '/activity',
        perPage: 15,
        emptyText: 'No activity recorded',
        emptyHint: 'Actions performed in the panel will appear here.',
        columns: [
            {
                label: 'When',
                render: function (row) { return UI.escape(window.APP.formatDateTime(row.created_at)); }
            },
            {
                label: 'Actor',
                render: function (row) {
                    if (!row.user_name) {
                        return '<span class="badge badge-muted">System</span>';
                    }
                    return '<div class="font-semibold">' + UI.escape(row.user_name) + '</div>' +
                        (row.role_name ? '<div class="text-muted text-sm">' + UI.escape(row.role_name) + '</div>' : '');
                }
            },
            {
                label: 'Action',
                render: function (row) {
                    return actionBadge(row.action) +
                        '<div class="text-muted text-sm mt-1">' + UI.escape(window.APP.titleCase(String(row.action || '').replace('.', ' '))) + '</div>';
                }
            },
            {
                label: 'Entity',
                render: function (row) {
                    return UI.escape(row.entity_type || '\u2014') + (row.entity_id ? ' <span class="text-muted">#' + window.APP.number(row.entity_id) + '</span>' : '');
                }
            },
            { label: 'Details', render: function (row) { return UI.escape(row.details || '\u2014'); } },
            { label: 'IP', render: function (row) { return UI.escape(row.ip_address || '\u2014'); } }
        ],
        onLoaded: function (items, data) {
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' entr' + (data.pagination.total === 1 ? 'y' : 'ies');
            }
        }
    });
})();
