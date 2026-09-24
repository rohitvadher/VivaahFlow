(function () {
    'use strict';

    window.Tables.create({
        table: UI.qs('[data-table]'),
        endpoint: '/portal/bookings',
        emptyText: 'No bookings yet',
        emptyHint: 'Accept a quotation and our team will create your booking.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-medium">' + UI.escape(row.reference_no) + '</span>'; } },
            { label: 'Event date', render: function (row) { return row.event_date ? APP.formatDate(row.event_date) : '\u2014'; } },
            { label: 'Type', render: function (row) { return UI.escape(row.event_type || '\u2014'); } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } },
            { label: 'Total', render: function (row) { return UI.escape(row.total_formatted || row.total_amount || '\u2014'); } },
            {
                label: 'Balance',
                render: function (row) {
                    if (row.payment_status === 'paid') {
                        return '<span class="badge badge-success">Paid in full</span>';
                    }
                    return UI.escape(row.remaining_formatted || '\u2014');
                }
            }
        ],
        rowActions: function (row) {
            return '<a class="btn btn-soft btn-sm" href="' + APP.route('account.booking', { id: row.id }) + '">' + UI.icon('eye') + ' View</a>';
        }
    });
})();
