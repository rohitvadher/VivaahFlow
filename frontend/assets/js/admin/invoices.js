(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('invoices-table');
    var modal = document.getElementById('invoice-modal');
    var form = document.getElementById('invoice-form');
    var submit = form.querySelector('[data-submit]');
    var bookingSelect = form.querySelector('[data-booking-select]');
    var bookings = [];

    function rowActions(row) {
        var html = '<button type="button" class="btn-icon" data-action="view" data-id="' + row.id + '" title="Open invoice"><i data-lucide="eye"></i></button>';
        var status = row.computed_status || row.status;
        if (status === 'draft') {
            html += '<button type="button" class="btn-icon" data-action="status" data-status="issued" data-id="' + row.id + '" data-permission="invoices.update" title="Mark issued"><i data-lucide="send"></i></button>';
        }
        if (status === 'issued') {
            html += '<button type="button" class="btn-icon" data-action="status" data-status="draft" data-id="' + row.id + '" data-permission="invoices.update" title="Move to draft"><i data-lucide="file-text"></i></button>';
        }
        if (row.status !== 'cancelled') {
            html += '<button type="button" class="btn-icon" data-action="status" data-status="cancelled" data-id="' + row.id + '" data-permission="invoices.update" title="Cancel invoice" style="color:var(--danger)"><i data-lucide="circle-x"></i></button>';
        }
        html += '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="invoices.delete" title="Delete invoice" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
        return html;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'invoices-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/invoices',
        perPage: 10,
        emptyText: 'No invoices yet',
        emptyHint: 'Generate an invoice from a booking to see it here.',
        columns: [
            {
                label: 'Invoice',
                render: function (row) {
                    return '<a class="link-brand font-semibold" href="' + window.APP.route('manage.invoice', { id: row.id }) + '">' + UI.escape(row.invoice_number) + '</a>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.booking_no) + '</div>';
                }
            },
            {
                label: 'Customer',
                render: function (row) { return UI.escape(row.customer_name); }
            },
            {
                label: 'Dates',
                render: function (row) {
                    return 'Issued ' + UI.escape(window.APP.formatDate(row.issue_date)) +
                        '<div class="text-muted text-sm">Due ' + UI.escape(window.APP.formatDate(row.due_date)) + '</div>';
                }
            },
            { label: 'Total', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.total_formatted) + '</span>'; } },
            {
                label: 'Balance',
                render: function (row) {
                    if (Number(row.remaining_cents) === 0) {
                        return '<span class="badge badge-success">Settled</span>';
                    }
                    return '<div class="font-semibold">' + UI.escape(row.remaining_formatted) + '</div>' +
                        '<div class="text-muted text-sm">Paid ' + UI.escape(row.paid_formatted) + '</div>';
                }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.computed_status || row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' invoice' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openInvoice() {
        UI.clear(form);
        form.issue_date.value = new Date().toISOString().slice(0, 10);
        UI.openModal(modal);
    }

    UI.qs('[data-open-invoice]').addEventListener('click', openInvoice);

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var record = records[Number(trigger.getAttribute('data-id'))];
        if (!record) {
            return;
        }
        var action = trigger.getAttribute('data-action');

        if (action === 'view') {
            window.location.href = window.APP.route('manage.invoice', { id: record.id });
        } else if (action === 'status') {
            var status = trigger.getAttribute('data-status');
            API.patch('/invoices/' + record.id + '/status', { status: status }).then(function () {
                Alerts.success('Invoice status updated.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        } else if (action === 'delete') {
            Alerts.confirm({ title: 'Delete invoice?', text: record.invoice_number + ' will be removed permanently.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/invoices/' + record.id).then(function () {
                    Alerts.success('Invoice deleted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!bookingSelect.value) {
            Alerts.error('Select a booking first.');
            return;
        }
        UI.setLoading(submit, true);
        API.post('/invoices', UI.serialize(form)).then(function (data) {
            UI.setLoading(submit, false);
            UI.closeModal(modal);
            Alerts.success('Invoice generated.');
            table.reload();
            if (data && data.id) {
                window.location.href = window.APP.route('manage.invoice', { id: data.id });
            }
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });

    API.get('/bookings', { per_page: 100 }).then(function (data) {
        bookings = (data && data.items) || [];
        UI.populate(bookingSelect, bookings, {
            placeholder: 'Select booking',
            label: function (item) { return item.reference_no + ' \u00B7 ' + item.customer_name + ' \u00B7 ' + item.total_formatted; }
        });
        if (new URLSearchParams(window.location.search).get('booking_id')) {
            bookingSelect.value = new URLSearchParams(window.location.search).get('booking_id');
        }
    }).catch(function () { return; });
})();
