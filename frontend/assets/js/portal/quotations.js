(function () {
    'use strict';

    window.Tables.create({
        table: UI.qs('[data-table]'),
        endpoint: '/portal/quotations',
        emptyText: 'No quotations yet',
        emptyHint: 'Once you submit an enquiry our team will prepare a proposal.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-medium">' + UI.escape(row.reference_no) + '</span>'; } },
            { label: 'Total', render: function (row) { return UI.escape(row.total_formatted || row.total_amount || '\u2014'); } },
            { label: 'Valid until', render: function (row) { return row.valid_until ? APP.formatDate(row.valid_until) : '\u2014'; } },
            {
                label: 'Status',
                render: function (row) {
                    return UI.statusBadge(row.status) + (row.is_expired ? ' <span class="badge badge-warning">Expired</span>' : '');
                }
            },
            { label: 'Created', render: function (row) { return APP.formatDate(row.created_at); } }
        ],
        rowActions: function (row) {
            return '<a class="btn btn-soft btn-sm" href="' + APP.route('account.quotation', { id: row.id }) + '">' + UI.icon('eye') + ' View</a>';
        }
    });
})();
