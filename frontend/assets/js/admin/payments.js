(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('payments-table');
    var modal = document.getElementById('payment-modal');
    var form = document.getElementById('payment-form');
    var submit = form.querySelector('[data-submit]');
    var bookingSelect = form.querySelector('[data-booking-select]');
    var balanceBox = form.querySelector('[data-booking-balance]');
    var bookings = [];

    function toNumber(value) {
        var parsed = String(value === null || value === undefined ? '' : value).replace(/[^0-9.-]/g, '');
        return parsed === '' || parsed === '-' ? 0 : parseFloat(parsed);
    }

    function remainingOf(booking) {
        return toNumber(booking.total_amount) - toNumber(booking.paid);
    }

    function rowActions(row) {
        var html = '<button type="button" class="btn-icon" data-action="booking" data-id="' + row.id + '" title="Open booking"><i data-lucide="external-link"></i></button>';
        if (row.status === 'recorded') {
            html += '<button type="button" class="btn-icon" data-action="reverse" data-id="' + row.id + '" data-permission="payments.reverse" title="Reverse payment" style="color:var(--danger)"><i data-lucide="rotate-ccw"></i></button>';
        }
        return html;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'payments-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/payments',
        perPage: 10,
        emptyText: 'No payments recorded',
        emptyHint: 'Record a payment against a booking to see it here.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.reference_no) + '</span>'; } },
            {
                label: 'Booking',
                render: function (row) {
                    return '<a class="link-brand" href="' + window.APP.route('manage.booking', { id: row.booking_id }) + '">' + UI.escape(row.booking_no) + '</a>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.customer_name) + '</div>';
                }
            },
            { label: 'Date', render: function (row) { return UI.escape(window.APP.formatDate(row.payment_date)); } },
            { label: 'Method', render: function (row) { return UI.escape(row.method); } },
            { label: 'Amount', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.amount_formatted) + '</span>'; } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' payment' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function refreshBalance() {
        var booking = bookings.filter(function (item) { return Number(item.id) === Number(bookingSelect.value); })[0];
        if (!booking) {
            balanceBox.innerHTML = '';
            return;
        }
        var remaining = remainingOf(booking);
        balanceBox.innerHTML = '<div class="detail-list">' +
            Workflow.detailRow('Total', UI.escape(booking.total_formatted)) +
            Workflow.detailRow('Paid', UI.escape(booking.paid_formatted)) +
            Workflow.detailRow('Remaining', UI.escape(booking.remaining_formatted)) +
            '</div>';
        if (remaining <= 0) {
            form.amount.value = '0.00';
            form.amount.setAttribute('max', '0');
        } else {
            form.amount.value = remaining.toFixed(2);
            form.amount.setAttribute('max', String(remaining));
        }
    }

    function openPayment() {
        UI.clear(form);
        form.payment_date.value = new Date().toISOString().slice(0, 10);
        refreshBalance();
        UI.openModal(modal);
    }

    UI.qs('[data-open-payment]').addEventListener('click', openPayment);
    bookingSelect.addEventListener('change', refreshBalance);

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var record = records[Number(trigger.getAttribute('data-id'))];
        if (!record) {
            return;
        }
        if (trigger.getAttribute('data-action') === 'booking') {
            window.location.href = window.APP.route('manage.booking', { id: record.booking_id });
        } else if (trigger.getAttribute('data-action') === 'reverse') {
            Alerts.confirm({ title: 'Reverse payment?', text: record.reference_no + ' (' + record.amount_formatted + ') will be reversed and balances recalculated.', confirmText: 'Reverse', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.post('/payments/' + record.id + '/reverse').then(function () {
                    Alerts.success('Payment reversed.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var booking = bookings.filter(function (item) { return Number(item.id) === Number(bookingSelect.value); })[0];
        var amount = Number(form.amount.value);
        if (!booking) {
            Alerts.error('Select a booking first.');
            return;
        }
        if (amount <= 0) {
            Alerts.error('Enter an amount greater than zero.');
            return;
        }
        if (amount > remainingOf(booking)) {
            Alerts.error('Amount exceeds the remaining balance of ' + booking.remaining_formatted + '.');
            return;
        }
        UI.setLoading(submit, true);
        API.post('/payments', UI.serialize(form)).then(function () {
            UI.setLoading(submit, false);
            UI.closeModal(modal);
            Alerts.success('Payment recorded.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });

    API.get('/bookings', { per_page: 100 }).then(function (data) {
        bookings = (data && data.items) || [];
        var options = bookings.filter(function (item) {
            return item.status !== 'cancelled' && item.payment_status !== 'paid';
        });
        UI.populate(bookingSelect, options, {
            placeholder: 'Select booking',
            label: function (item) { return item.reference_no + ' \u00B7 ' + item.customer_name + ' \u00B7 due ' + item.remaining_formatted; }
        });
    }).catch(function () { return; });
})();
