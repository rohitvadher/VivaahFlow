(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('bookings-table');
    var scheduleModal = document.getElementById('schedule-modal');
    var scheduleForm = document.getElementById('schedule-form');
    var scheduleSubmit = scheduleForm.querySelector('[data-submit]');
    var paymentModal = document.getElementById('payment-modal');
    var paymentForm = document.getElementById('payment-form');
    var paymentSubmit = paymentForm.querySelector('[data-submit]');

    function orderOf(status) {
        return Workflow.bookingNext({ status: status });
    }

    function toNumber(value) {
        var parsed = String(value === null || value === undefined ? '' : value).replace(/[^0-9.-]/g, '');
        return parsed === '' || parsed === '-' ? 0 : parseFloat(parsed);
    }

    function remainingOf(record) {
        return toNumber(record.total_amount) - toNumber(record.paid);
    }

    function rowActions(row) {
        var actions = '<button type="button" class="btn-icon" data-action="view" data-id="' + row.id + '" title="Open booking"><i data-lucide="eye"></i></button>';
        if (row.status !== 'completed' && row.status !== 'cancelled') {
            actions += '<button type="button" class="btn-icon" data-action="schedule" data-id="' + row.id + '" data-permission="bookings.update" title="Schedule"><i data-lucide="calendar-days"></i></button>';
        }
        if (row.status !== 'cancelled' && row.payment_status !== 'paid') {
            actions += '<button type="button" class="btn-icon" data-action="payment" data-id="' + row.id + '" data-permission="payments.create" title="Record payment" style="color:var(--success)"><i data-lucide="wallet"></i></button>';
        }
        orderOf(row.status).forEach(function (status) {
            var danger = status === 'cancelled';
            actions += '<button type="button" class="btn-icon" data-action="status" data-status="' + status + '" data-id="' + row.id + '" data-permission="bookings.status" title="Mark ' + window.APP.titleCase(status) + '"' + (danger ? ' style="color:var(--danger)"' : '') + '><i data-lucide="' + (danger ? 'circle-x' : 'circle-arrow-right') + '"></i></button>';
        });
        return actions;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'bookings-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        filters: [
            { el: '[data-filter-date-from]', key: 'date_from' },
            { el: '[data-filter-date-to]', key: 'date_to' }
        ],
        endpoint: '/bookings',
        perPage: 10,
        emptyText: 'No bookings yet',
        emptyHint: 'Accept a quotation and create a booking to see it here.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.reference_no) + '</span>'; } },
            {
                label: 'Customer',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.customer_name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.customer_phone || row.customer_email || '') + '</div>';
                }
            },
            {
                label: 'Event',
                render: function (row) {
                    return UI.escape(window.APP.formatDate(row.event_date)) +
                        '<div class="text-muted text-sm">' + UI.escape(row.event_type || '\u2014') + '</div>';
                }
            },
            { label: 'Total', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.total_formatted) + '</span>'; } },
            {
                label: 'Payment',
                render: function (row) {
                    if (row.payment_status === 'paid') {
                        return '<span class="badge badge-success">Paid</span>';
                    }
                    return '<div class="font-semibold">' + UI.escape(row.paid_formatted) + '</div>' +
                        '<div class="text-muted text-sm">Due ' + UI.escape(row.remaining_formatted) + '</div>';
                }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' booking' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openSchedule(record) {
        UI.clear(scheduleForm);
        scheduleForm.booking_id.value = record.id;
        scheduleForm.event_date.value = record.event_date || '';
        scheduleForm.venue_address.value = record.venue_address || '';
        UI.openModal(scheduleModal);
    }

    function openPayment(record) {
        UI.clear(paymentForm);
        paymentForm.booking_id.value = record.id;
        paymentForm.amount.value = remainingOf(record).toFixed(2);
        paymentForm.payment_date.value = new Date().toISOString().slice(0, 10);
        UI.qs('[data-payment-summary]', paymentModal).textContent =
            'Total ' + record.total_formatted + ' \u00B7 Paid ' + record.paid_formatted + ' \u00B7 Remaining ' + record.remaining_formatted;
        UI.openModal(paymentModal);
    }

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var id = Number(trigger.getAttribute('data-id'));
        var record = records[id];
        if (!record) {
            return;
        }
        var action = trigger.getAttribute('data-action');

        if (action === 'view') {
            window.location.href = window.APP.route('manage.booking', { id: id });
        } else if (action === 'schedule') {
            openSchedule(record);
        } else if (action === 'payment') {
            openPayment(record);
        } else if (action === 'status') {
            var status = trigger.getAttribute('data-status');
            Alerts.confirm({
                title: 'Update booking status?',
                text: record.reference_no + ' will be marked as ' + window.APP.titleCase(status) + '.',
                confirmText: 'Confirm',
                danger: status === 'cancelled'
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.patch('/bookings/' + id + '/status', { status: status }).then(function () {
                    Alerts.success('Booking marked as ' + window.APP.titleCase(status) + '.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    scheduleForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = { event_date: scheduleForm.event_date.value, venue_address: scheduleForm.venue_address.value };
        UI.setLoading(scheduleSubmit, true);
        API.post('/bookings/' + scheduleForm.booking_id.value + '/schedule', payload).then(function () {
            UI.setLoading(scheduleSubmit, false);
            UI.closeModal(scheduleModal);
            Alerts.success('Booking scheduled.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(scheduleSubmit, false);
            if (error.errors) {
                UI.showErrors(scheduleForm, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    paymentForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var record = records[Number(paymentForm.booking_id.value)];
        var amount = Number(paymentForm.amount.value);
        if (record && amount > remainingOf(record)) {
            Alerts.error('Amount exceeds the remaining balance of ' + record.remaining_formatted + '.');
            return;
        }
        var payload = UI.serialize(paymentForm);
        var bookingId = payload.booking_id;
        delete payload.booking_id;
        payload.amount = amount;
        UI.setLoading(paymentSubmit, true);
        API.post('/payments', Object.assign({ booking_id: Number(bookingId) }, payload)).then(function () {
            UI.setLoading(paymentSubmit, false);
            UI.closeModal(paymentModal);
            Alerts.success('Payment recorded.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(paymentSubmit, false);
            if (error.errors) {
                UI.showErrors(paymentForm, error.errors);
            }
            Alerts.error(error.message);
        });
    });
})();
