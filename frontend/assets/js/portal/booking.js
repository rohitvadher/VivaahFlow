(function () {
    'use strict';

    var root = UI.qs('[data-booking]');
    if (!root) {
        return;
    }

    var id = Number(root.getAttribute('data-id'));

    function money(formatted, fallback) {
        if (formatted) {
            return formatted;
        }
        if (fallback && isNaN(Number(fallback))) {
            return fallback;
        }
        return Site.money(fallback);
    }

    function timeLabel(value) {
        if (!value) {
            return '';
        }
        var parts = String(value).split(':');
        var hours = Number(parts[0]);
        var minutes = parts[1] || '00';
        if (!isFinite(hours)) {
            return '';
        }
        var suffix = hours >= 12 ? 'PM' : 'AM';
        var display = hours % 12;
        if (display === 0) {
            display = 12;
        }
        return display + ':' + minutes + ' ' + suffix;
    }

    function render(booking, payments) {
        var services = booking.services || [];
        var events = booking.events || [];
        var discount = Number(booking.discount_amount);
        var paidInFull = booking.payment_status === 'paid';
        var relatedPayments = (payments || []).filter(function (payment) {
            return Number(payment.booking_id) === Number(booking.id);
        });

        root.innerHTML = '' +
            '<div class="grid gap-6 lg:grid-cols-3">' +
                '<div class="lg:col-span-2 grid gap-6">' +
                    '<div class="card">' +
                        '<div class="card-head">' +
                            '<div>' +
                                '<h2 class="card-title">' + UI.escape(booking.reference_no) + '</h2>' +
                                '<p class="card-subtitle">' + UI.escape(booking.event_type || 'Event') + ' \u00b7 ' + (booking.event_date ? APP.formatDate(booking.event_date) : '') + '</p>' +
                            '</div>' +
                            UI.statusBadge(booking.status) +
                        '</div>' +
                        '<div class="card-pad">' +
                            '<div class="detail-list">' +
                                '<div class="detail-row"><span>Venue</span><strong>' + UI.escape(booking.venue_address || '\u2014') + '</strong></div>' +
                                '<div class="detail-row"><span>Booking date</span><strong>' + APP.formatDate(booking.booking_date) + '</strong></div>' +
                                (booking.quotation_no ? '<div class="detail-row"><span>Quotation</span><strong>' + UI.escape(booking.quotation_no) + '</strong></div>' : '') +
                            '</div>' +
                            (booking.notes ? '<p class="text-sm text-muted mt-4">' + UI.escape(booking.notes) + '</p>' : '') +
                        '</div>' +
                    '</div>' +

                    (services.length > 0
                        ? '<div class="card"><div class="card-head"><h3 class="card-title">Services</h3></div><div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th style="text-align:right">Amount</th></tr></thead><tbody>' +
                            services.map(function (service) {
                                return '<tr><td>' + UI.escape(service.item_name) + '</td><td>' + Number(service.quantity || 1) + '</td><td style="text-align:right" class="font-medium">' + Site.money(service.amount) + '</td></tr>';
                            }).join('') + '</tbody></table></div></div>'
                        : '') +

                    (events.length > 0
                        ? '<div class="card"><div class="card-head"><h3 class="card-title">Event schedule</h3></div><div class="card-pad"><div class="timeline">' +
                            events.map(function (event) {
                                var assignments = event.assignments || [];
                                return '<div class="timeline-item">' +
                                    '<div class="flex items-start justify-between gap-3 flex-wrap">' +
                                        '<div>' +
                                            '<div class="font-semibold">' + UI.escape(event.title) + '</div>' +
                                            '<div class="text-xs text-muted">' + APP.formatDate(event.event_date) +
                                                (event.start_time ? ' \u00b7 ' + timeLabel(event.start_time) : '') +
                                                (event.end_time ? ' \u2013 ' + timeLabel(event.end_time) : '') + '</div>' +
                                        '</div>' +
                                        UI.statusBadge(event.status) +
                                    '</div>' +
                                    (event.venue_address ? '<div class="text-sm text-muted mt-1">' + UI.escape(event.venue_address) + '</div>' : '') +
                                    (assignments.length > 0
                                        ? '<div class="flex flex-wrap gap-2 mt-2">' + assignments.map(function (assignment) {
                                            return '<span class="badge badge-plain">' + UI.escape(assignment.staff_name) + (assignment.designation ? ' \u00b7 ' + UI.escape(assignment.designation) : '') + '</span>';
                                        }).join('') + '</div>'
                                        : '') +
                                '</div>';
                            }).join('') + '</div></div></div>'
                        : '') +
                '</div>' +

                '<aside class="grid gap-6" style="align-content:start">' +
                    '<div class="card card-pad">' +
                        '<h3 class="card-title mb-3">Payment summary</h3>' +
                        '<div class="totals-list">' +
                            '<div class="totals-row"><span>Subtotal</span><strong>' + money(booking.subtotal_formatted, booking.subtotal) + '</strong></div>' +
                            (discount > 0 ? '<div class="totals-row"><span>Discount</span><strong style="color:var(--success)">\u2212 ' + money(booking.discount_formatted, booking.discount_amount) + '</strong></div>' : '') +
                            '<div class="totals-row is-total"><span>Total</span><strong>' + money(booking.total_formatted, booking.total_amount) + '</strong></div>' +
                            '<div class="totals-row"><span>Paid</span><strong style="color:var(--success)">' + money(booking.paid_formatted, booking.paid_amount) + '</strong></div>' +
                            (paidInFull
                                ? '<div class="totals-row"><span>Status</span><strong><span class="badge badge-success">Paid in full</span></strong></div>'
                                : '<div class="totals-row is-total"><span>Balance due</span><strong>' + money(booking.remaining_formatted, booking.remaining_amount) + '</strong></div>') +
                        '</div>' +
                        '<div class="flex flex-col gap-2 mt-4">' +
                            '<a class="btn btn-outline btn-block" href="' + APP.route('account.payments') + '">' + UI.icon('wallet') + ' Payment history</a>' +
                            '<a class="btn btn-outline btn-block" href="' + APP.route('account.invoices') + '">' + UI.icon('receipt') + ' Invoices</a>' +
                        '</div>' +
                    '</div>' +
                    (relatedPayments.length > 0
                        ? '<div class="card card-pad"><h3 class="card-title mb-3">Recent payments</h3><div class="detail-list">' +
                            relatedPayments.slice(0, 4).map(function (payment) {
                                return '<div class="detail-row"><span>' + APP.formatDate(payment.payment_date) + '</span><strong>' + UI.escape(payment.amount_formatted || Site.money(payment.amount)) + '</strong></div>';
                            }).join('') + '</div></div>'
                        : '') +
                '</aside>' +
            '</div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function load() {
        if (!id) {
            root.innerHTML = Site.empty('Booking not found', 'The link may be broken.');
            return;
        }
        Promise.all([
            API.get('/portal/bookings/' + id),
            API.get('/portal/payments').catch(function () { return { data: { items: [] } }; })
        ]).then(function (results) {
            render(results[0].data || {}, results[1].data ? results[1].data.items : []);
        }).catch(function (error) {
            root.innerHTML = Site.empty('Booking not found', error.message || 'This booking is unavailable.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
