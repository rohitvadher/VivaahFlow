(function () {
    'use strict';

    window.Tables.create({
        table: UI.qs('[data-table]'),
        endpoint: '/portal/payments',
        emptyText: 'No payments recorded',
        emptyHint: 'Payments appear here as soon as our team records them.',
        columns: [
            { label: 'Date', render: function (row) { return APP.formatDate(row.payment_date); } },
            { label: 'Reference', render: function (row) { return '<span class="font-medium">' + UI.escape(row.reference_no) + '</span>'; } },
            { label: 'Booking', render: function (row) { return UI.escape(row.booking_no || '\u2014'); } },
            { label: 'Method', render: function (row) { return UI.escape(row.method || '\u2014'); } },
            { label: 'Amount', render: function (row) { return UI.escape(row.amount_formatted || Site.money(row.amount)); } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        onLoaded: function (items, data) {
            var node = UI.qs('[data-total-paid]');
            if (node) {
                node.textContent = (data && data.total_paid) || '\u20b90.00';
            }
        }
    });
})();
