(function () {
    'use strict';

    function today() {
        return new Date().toISOString().slice(0, 10);
    }

    function renderStats(data) {
        var node = UI.qs('[data-stats]');
        if (!node) {
            return;
        }
        var openQuotations = (data.quotations || []).filter(function (quotation) {
            return quotation.status === 'sent' || quotation.status === 'draft';
        }).length;
        var activeBookings = (data.bookings || []).filter(function (booking) {
            return booking.status !== 'completed' && booking.status !== 'cancelled';
        }).length;
        var cards = [
            { label: 'Enquiries', value: (data.enquiries || []).length, icon: 'messages-square', href: APP.route('account.enquiries') },
            { label: 'Open quotations', value: openQuotations, icon: 'file-text', href: APP.route('account.quotations') },
            { label: 'Active bookings', value: activeBookings, icon: 'calendar-check', href: APP.route('account.bookings') },
            { label: 'Total paid', value: data.totalPaid || '\u2014', icon: 'wallet', href: APP.route('account.payments'), isMoney: true }
        ];
        node.innerHTML = cards.map(function (card) {
            return '' +
                '<a class="card card-pad flex items-center gap-3" href="' + card.href + '">' +
                    '<span class="stat-icon">' + UI.icon(card.icon) + '</span>' +
                    '<div>' +
                        '<div class="stat-value"' + (card.isMoney ? ' style="font-size:22px"' : '') + '>' + UI.escape(String(card.value)) + '</div>' +
                        '<div class="stat-label">' + UI.escape(card.label) + '</div>' +
                    '</div>' +
                '</a>';
        }).join('');
    }

    function renderUpcoming(bookings) {
        var node = UI.qs('[data-upcoming]');
        if (!node) {
            return;
        }
        var upcoming = (bookings || []).filter(function (booking) {
            return booking.event_date && booking.event_date >= today() && booking.status !== 'cancelled';
        }).sort(function (a, b) {
            return a.event_date < b.event_date ? -1 : 1;
        });
        if (upcoming.length === 0) {
            node.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">No upcoming events</div><p class="text-sm text-muted mt-1">Once your booking is confirmed your event will appear here.</p></div></div>';
            return;
        }
        node.innerHTML = upcoming.slice(0, 3).map(function (booking) {
            return '' +
                '<div class="card card-pad mb-4">' +
                    '<div class="flex items-start justify-between gap-3 flex-wrap">' +
                        '<div>' +
                            '<div class="text-xs text-muted">' + APP.formatDate(booking.event_date) + '</div>' +
                            '<h3 class="text-lg font-semibold mt-1">' + UI.escape(booking.event_type || 'Event') + '</h3>' +
                            '<p class="text-sm text-muted">' + UI.escape(booking.venue_address || 'Venue to be confirmed') + '</p>' +
                        '</div>' +
                        UI.statusBadge(booking.status) +
                    '</div>' +
                    '<div class="flex items-center gap-4 mt-3 text-sm">' +
                        '<span class="text-muted">Booking ' + UI.escape(booking.reference_no) + '</span>' +
                        '<span class="ml-auto site-price">' + UI.escape(booking.total_formatted || booking.total_amount || '') + '</span>' +
                    '</div>' +
                    '<a class="btn btn-soft btn-sm mt-3" href="' + APP.route('account.booking', { id: booking.id }) + '">View details</a>' +
                '</div>';
        }).join('');
    }

    function renderQuick() {
        var node = UI.qs('[data-quick]');
        if (!node) {
            return;
        }
        var links = [
            { label: 'Start a new enquiry', icon: 'plus-circle', href: APP.route('contact') },
            { label: 'View quotations', icon: 'file-text', href: APP.route('account.quotations') },
            { label: 'Payment history', icon: 'wallet', href: APP.route('account.payments') },
            { label: 'Update profile', icon: 'user-round', href: APP.route('account.profile') }
        ];
        node.innerHTML = '<div class="card card-pad">' +
            '<h2 class="card-title mb-3">Quick actions</h2>' +
            '<div class="grid gap-2">' +
                links.map(function (link) {
                    return '<a class="btn btn-outline btn-block justify-start" href="' + link.href + '">' + UI.icon(link.icon) + ' ' + UI.escape(link.label) + '</a>';
                }).join('') +
            '</div></div>';
    }

    function renderEnquiries(enquiries) {
        var node = UI.qs('[data-recent-enquiries]');
        if (!node) {
            return;
        }
        if (!enquiries || enquiries.length === 0) {
            node.innerHTML = '<div class="table-empty"><div class="empty-title">No enquiries yet</div><p class="text-sm text-muted mt-1">Submit your first enquiry to get started.</p></div>';
            return;
        }
        node.innerHTML = '' +
            '<table class="table"><thead><tr><th>Reference</th><th>Event</th><th>Date</th><th>Status</th></tr></thead><tbody>' +
                enquiries.slice(0, 5).map(function (row) {
                    return '<tr>' +
                        '<td class="font-medium">' + UI.escape(row.reference_no) + '</td>' +
                        '<td>' + UI.escape(row.event_type || '\u2014') + '</td>' +
                        '<td>' + (row.event_date ? APP.formatDate(row.event_date) : '\u2014') + '</td>' +
                        '<td>' + UI.statusBadge(row.status) + '</td>' +
                    '</tr>';
                }).join('') +
            '</tbody></table>';
    }

    function boot() {
        renderQuick();
        Promise.all([
            API.get('/portal/profile'),
            API.get('/portal/enquiries'),
            API.get('/portal/quotations'),
            API.get('/portal/bookings'),
            API.get('/portal/payments')
        ]).then(function (results) {
            var data = {
                profile: results[0].data,
                enquiries: results[1].data || [],
                quotations: results[2].data || [],
                bookings: results[3].data || [],
                totalPaid: results[4].data ? results[4].data.total_paid : null
            };
            renderStats(data);
            renderUpcoming(data.bookings);
            renderEnquiries(data.enquiries);
            if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
        }).catch(function (error) {
            Alerts.error(error.message || 'Unable to load your dashboard.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
