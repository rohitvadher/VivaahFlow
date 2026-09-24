(function () {
    'use strict';

    window.Tables.create({
        table: UI.qs('[data-table]'),
        endpoint: '/portal/invoices',
        emptyText: 'No invoices yet',
        emptyHint: 'Invoices will appear here once your booking is invoiced.',
        columns: [
            { label: 'Invoice', render: function (row) { return '<span class="font-medium">' + UI.escape(row.invoice_number) + '</span>'; } },
            { label: 'Booking', render: function (row) { return UI.escape(row.booking_no || '\u2014'); } },
            { label: 'Issued', render: function (row) { return row.issue_date ? APP.formatDate(row.issue_date) : '\u2014'; } },
            { label: 'Total', render: function (row) { return UI.escape(row.total_formatted || Site.money(row.total_amount)); } },
            {
                label: 'Balance',
                render: function (row) {
                    if (Number(row.remaining_cents) <= 0) {
                        return '<span class="badge badge-success">Settled</span>';
                    }
                    return UI.escape(row.remaining_formatted || '\u2014');
                }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.computed_status || row.status); } }
        ],
        rowActions: function (row) {
            return '<a class="btn btn-soft btn-sm" href="' + APP.route('account.invoice', { id: row.id }) + '">' + UI.icon('eye') + ' View</a>';
        }
    });
})();
