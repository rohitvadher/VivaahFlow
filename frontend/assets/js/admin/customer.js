(function () {
    'use strict';

    var id = Number(window.APP.recordId() || 0);
    var current = null;
    var form = document.getElementById('customer-form');
    var modal = document.getElementById('customer-modal');
    var submitBtn = form.querySelector('[data-submit]');

    if (!id) {
        Alerts.error('Customer not found.');
        return;
    }

    function detail(label, value) {
        return '<div class="detail-row"><dt>' + UI.escape(label) + '</dt><dd>' + value + '</dd></div>';
    }

    function empty(title) {
        return '<div class="table-empty"><div class="empty-title">' + UI.escape(title) + '</div></div>';
    }

    function table(headers, rows) {
        return '<div class="table-wrap"><table class="table"><thead><tr>' +
            headers.map(function (header) { return '<th>' + UI.escape(header) + '</th>'; }).join('') +
            '</tr></thead><tbody>' + rows.join('') + '</tbody></table></div>';
    }

    function renderBookings(rows) {
        var host = UI.qs('[data-section="bookings"]');
        if (!rows || !rows.length) {
            host.innerHTML = empty('No bookings yet');
            return;
        }
        host.innerHTML = table(['Reference', 'Event', 'Amount', 'Paid', 'Status'], rows.map(function (row) {
            return '<tr>' +
                '<td><a class="link-brand" href="' + window.APP.route('manage.booking', { id: row.id }) + '">' + UI.escape(row.reference_no) + '</a></td>' +
                '<td>' + UI.escape(window.APP.formatDate(row.event_date)) + '</td>' +
                '<td>' + UI.escape(window.APP.money(Math.round(Number(row.total_amount) * 100))) + '</td>' +
                '<td>' + UI.escape(window.APP.money(Math.round(Number(row.paid_amount) * 100))) + '</td>' +
                '<td>' + UI.statusBadge(row.status) + '</td>' +
                '</tr>';
        }));
    }

    function renderQuotations(rows) {
        var host = UI.qs('[data-section="quotations"]');
        if (!rows || !rows.length) {
            host.innerHTML = empty('No quotations yet');
            return;
        }
        host.innerHTML = table(['Reference', 'Total', 'Valid Until', 'Status', 'Created'], rows.map(function (row) {
            return '<tr>' +
                '<td><a class="link-brand" href="' + window.APP.route('manage.quotation', { id: row.id }) + '">' + UI.escape(row.reference_no) + '</a></td>' +
                '<td>' + UI.escape(window.APP.money(Math.round(Number(row.total_amount) * 100))) + '</td>' +
                '<td>' + UI.escape(window.APP.formatDate(row.valid_until)) + '</td>' +
                '<td>' + UI.statusBadge(row.status) + '</td>' +
                '<td>' + UI.escape(window.APP.formatDate(row.created_at)) + '</td>' +
                '</tr>';
        }));
    }

    function renderPayments(rows) {
        var host = UI.qs('[data-section="payments"]');
        if (!rows || !rows.length) {
            host.innerHTML = empty('No payments yet');
            return;
        }
        host.innerHTML = table(['Reference', 'Amount', 'Method', 'Status', 'Date'], rows.map(function (row) {
            return '<tr>' +
                '<td>' + UI.escape(row.reference_no || '\u2014') + '</td>' +
                '<td>' + UI.escape(window.APP.money(Math.round(Number(row.amount) * 100))) + '</td>' +
                '<td>' + UI.escape(window.APP.titleCase(row.method || '')) + '</td>' +
                '<td>' + UI.statusBadge(row.status) + '</td>' +
                '<td>' + UI.escape(window.APP.formatDate(row.payment_date || row.created_at)) + '</td>' +
                '</tr>';
        }));
    }

    function renderReviews(rows) {
        var host = UI.qs('[data-section="reviews"]');
        if (!rows || !rows.length) {
            host.innerHTML = empty('No reviews yet');
            return;
        }
        host.innerHTML = '<div class="divide-y">' + rows.map(function (row) {
            return '<div style="padding:14px 20px">' +
                '<div class="flex items-center justify-between gap-3">' +
                '<span class="rating">' + Array.from({ length: 5 }).map(function (_, index) {
                    return '<i data-lucide="star" style="' + (index < Number(row.rating) ? '' : 'opacity:.25') + '"></i>';
                }).join('') + '</span>' +
                UI.statusBadge(row.status) +
                '</div>' +
                '<div class="text-sm mt-2">' + UI.escape(row.comment || row.title || '\u2014') + '</div>' +
                '<div class="text-muted text-xs mt-1">' + UI.escape(window.APP.formatDate(row.created_at)) + '</div>' +
                '</div>';
        }).join('') + '</div>';
    }

    function render(customer) {
        current = customer;
        UI.qs('[data-customer-name]').textContent = customer.name;
        UI.qs('[data-customer-meta]').innerHTML =
            UI.statusBadge(customer.status) +
            '<span class="badge badge-brand badge-plain">' + UI.escape(window.APP.titleCase(customer.source || 'manual')) + '</span>';

        var details = UI.qs('[data-customer-details]');
        details.innerHTML =
            detail('Email', customer.email ? '<a class="link-brand" href="mailto:' + UI.escape(customer.email) + '">' + UI.escape(customer.email) + '</a>' : '\u2014') +
            detail('Phone', customer.phone ? '<a class="link-brand" href="tel:' + UI.escape(customer.phone) + '">' + UI.escape(customer.phone) + '</a>' : '\u2014') +
            detail('Wedding date', UI.escape(window.APP.formatDate(customer.wedding_date))) +
            detail('Event type', UI.escape(customer.event_type || '\u2014')) +
            detail('City', UI.escape(customer.city || '\u2014')) +
            detail('Address', UI.escape(customer.address || '\u2014')) +
            detail('Added on', UI.escape(window.APP.formatDate(customer.created_at)));

        var history = customer.history || {};
        var paid = (history.payments || []).reduce(function (sum, row) {
            return sum + (row.status === 'reversed' ? 0 : Number(row.amount) || 0);
        }, 0);
        UI.qs('[data-stat="bookings"]').textContent = window.APP.number((history.bookings || []).length);
        UI.qs('[data-stat="quotations"]').textContent = window.APP.number((history.quotations || []).length);
        UI.qs('[data-stat="reviews"]').textContent = window.APP.number((history.reviews || []).length);
        UI.qs('[data-stat="paid"]').textContent = window.APP.money(Math.round(paid * 100));

        renderBookings(history.bookings);
        renderQuotations(history.quotations);
        renderPayments(history.payments);
        renderReviews(history.reviews);
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function load() {
        API.get('/customers/' + id).then(render).catch(function (error) {
            Alerts.error(error.message);
        });
    }

    UI.qs('[data-edit-customer]').addEventListener('click', function () {
        if (!current) {
            return;
        }
        UI.clear(form);
        UI.fill(form, current);
        UI.openModal(modal);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(form);
        delete payload.id;
        UI.setLoading(submitBtn, true);
        API.put('/customers/' + id, payload).then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success('Customer updated.');
            load();
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    load();
})();
