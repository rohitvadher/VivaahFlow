(function () {
    'use strict';

    window.Tables.create({
        table: UI.qs('[data-table]'),
        endpoint: '/portal/enquiries',
        emptyText: 'No enquiries yet',
        emptyHint: 'Use the New Enquiry button to tell us about your celebration.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-medium">' + UI.escape(row.reference_no) + '</span>'; } },
            { label: 'Event', render: function (row) { return UI.escape(row.event_type || '\u2014'); } },
            { label: 'Event date', render: function (row) { return row.event_date ? APP.formatDate(row.event_date) : '\u2014'; } },
            { label: 'Venue', render: function (row) { return UI.escape(row.venue_address || '\u2014'); } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } },
            { label: 'Submitted', render: function (row) { return APP.formatDate(row.created_at); } }
        ]
    });
})();
