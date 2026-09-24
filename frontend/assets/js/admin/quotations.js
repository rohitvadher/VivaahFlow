(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('quotations-table');
    var modal = document.getElementById('booking-modal');
    var form = document.getElementById('booking-form');
    var submitBtn = form.querySelector('[data-submit]');
    var activeQuotation = null;

    function rowActions(row) {
        var actions = '<button type="button" class="btn-icon" data-action="view" data-id="' + row.id + '" title="Open quotation"><i data-lucide="eye"></i></button>';
        if (row.status === 'draft') {
            actions += '<button type="button" class="btn-icon" data-action="send" data-id="' + row.id + '" data-permission="quotations.status" title="Mark as sent"><i data-lucide="send"></i></button>';
        }
        if (Workflow.quotationCanAccept(row)) {
            actions += '<button type="button" class="btn-icon" data-action="accept" data-id="' + row.id + '" data-permission="quotations.status" title="Mark accepted" style="color:var(--success)"><i data-lucide="check"></i></button>' +
                '<button type="button" class="btn-icon" data-action="reject" data-id="' + row.id + '" data-permission="quotations.status" title="Mark rejected" style="color:var(--danger)"><i data-lucide="x"></i></button>';
        }
        if (row.status === 'accepted') {
            actions += '<button type="button" class="btn-icon" data-action="booking" data-id="' + row.id + '" data-permission="bookings.create" title="Create booking"><i data-lucide="calendar-plus"></i></button>';
        }
        return actions;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'quotations-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/quotations',
        perPage: 10,
        emptyText: 'No quotations yet',
        emptyHint: 'Convert an enquiry into a quotation to get started.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.reference_no) + '</span>'; } },
            {
                label: 'Customer',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.customer_name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.customer_email || '') + '</div>';
                }
            },
            { label: 'Total', render: function (row) { return '<span class="font-semibold text-brand">' + UI.escape(row.total_formatted) + '</span>'; } },
            {
                label: 'Discount',
                render: function (row) {
                    return Number(row.discount_amount) > 0 ? UI.escape(row.discount_formatted) : '<span class="text-muted">\u2014</span>';
                }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.is_expired ? 'expired' : row.status); } },
            {
                label: 'Valid Until',
                render: function (row) {
                    if (!row.valid_until) {
                        return '<span class="text-muted">\u2014</span>';
                    }
                    var expired = row.is_expired;
                    return '<span' + (expired ? ' style="color:var(--danger)"' : '') + '>' + UI.escape(window.APP.formatDate(row.valid_until)) + '</span>';
                }
            }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' quotation' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openBookingModal(quotation) {
        activeQuotation = quotation;
        UI.clear(form);
        form.quotation_id.value = quotation.id;
        form.event_date.value = quotation.valid_until || '';
        UI.qs('[data-booking-summary]', modal).textContent = 'Converting ' + quotation.reference_no + ' \u00B7 ' + quotation.total_formatted + ' for ' + quotation.customer_name + '.';
        UI.openModal(modal);
    }

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var id = Number(trigger.getAttribute('data-id'));
        var action = trigger.getAttribute('data-action');
        var record = records[id];
        if (!record) {
            return;
        }

        if (action === 'view') {
            window.location.href = window.APP.route('manage.quotation', { id: id });
            return;
        }
        if (action === 'send') {
            API.patch('/quotations/' + id + '/status', { status: 'sent' }).then(function () {
                Alerts.success('Quotation marked as sent.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
            return;
        }
        if (action === 'accept') {
            Alerts.confirm({ title: 'Accept quotation?', text: record.reference_no + ' will be marked as accepted.', confirmText: 'Accept' }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.post('/quotations/' + id + '/accept').then(function () {
                    Alerts.success('Quotation accepted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
            return;
        }
        if (action === 'reject') {
            Alerts.confirm({ title: 'Reject quotation?', text: 'The linked enquiry will be closed.', confirmText: 'Reject', danger: true }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.post('/quotations/' + id + '/reject').then(function () {
                    Alerts.success('Quotation rejected.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
            return;
        }
        if (action === 'booking') {
            openBookingModal(record);
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(form);
        var quotationId = payload.quotation_id;
        delete payload.quotation_id;
        UI.setLoading(submitBtn, true);
        API.post('/bookings', Object.assign({ quotation_id: Number(quotationId) }, payload)).then(function (booking) {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success('Booking ' + booking.reference_no + ' created.');
            window.setTimeout(function () {
                window.location.href = window.APP.route('manage.booking', { id: booking.id });
            }, 400);
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });
})();
