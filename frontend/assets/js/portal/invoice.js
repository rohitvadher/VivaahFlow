(function () {
    'use strict';

    var root = UI.qs('[data-invoice]');
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

    function render(invoice) {
        var services = invoice.services || [];
        var discount = Number(invoice.discount_amount);
        var settled = Number(invoice.remaining_cents) <= 0;
        var company = (APP.siteSettings ? APP.siteSettings.company_name : '') || 'VivaahFlow';

        root.innerHTML = '' +
            '<div class="card card-pad" data-invoice-paper>' +
                '<div class="flex items-start justify-between gap-4 flex-wrap">' +
                    '<div>' +
                        '<h2 class="site-section-title" style="font-size:24px">' + UI.escape(company) + '</h2>' +
                        '<p class="text-sm text-muted" data-invoice-address>' + UI.escape(APP.siteSettings ? (APP.siteSettings.company_address || '') : '') + '</p>' +
                    '</div>' +
                    '<div style="text-align:right">' +
                        '<div class="text-xs text-muted">Invoice</div>' +
                        '<div class="font-semibold" style="font-size:20px">' + UI.escape(invoice.invoice_number) + '</div>' +
                        UI.statusBadge(invoice.computed_status || invoice.status) +
                    '</div>' +
                '</div>' +
                '<div class="detail-list mt-6">' +
                    '<div class="detail-row"><span>Booking</span><strong>' + UI.escape(invoice.booking_no || '\u2014') + '</strong></div>' +
                    '<div class="detail-row"><span>Event date</span><strong>' + (invoice.event_date ? APP.formatDate(invoice.event_date) : '\u2014') + '</strong></div>' +
                    '<div class="detail-row"><span>Issued</span><strong>' + (invoice.issue_date ? APP.formatDate(invoice.issue_date) : '\u2014') + '</strong></div>' +
                    '<div class="detail-row"><span>Due</span><strong>' + (invoice.due_date ? APP.formatDate(invoice.due_date) : '\u2014') + '</strong></div>' +
                '</div>' +
                (services.length > 0
                    ? '<div class="table-wrap mt-6"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th style="text-align:right">Amount</th></tr></thead><tbody>' +
                        services.map(function (service) {
                            return '<tr><td>' + UI.escape(service.item_name) + '</td><td>' + Number(service.quantity || 1) + '</td><td style="text-align:right">' + Site.money(service.amount) + '</td></tr>';
                        }).join('') + '</tbody></table></div>'
                    : '') +
                '<div class="totals-list mt-6" style="max-width:340px;margin-left:auto">' +
                    '<div class="totals-row"><span>Subtotal</span><strong>' + money(invoice.total_formatted, invoice.subtotal) + '</strong></div>' +
                    (discount > 0 ? '<div class="totals-row"><span>Discount</span><strong style="color:var(--success)">\u2212 ' + Site.money(invoice.discount_amount) + '</strong></div>' : '') +
                    '<div class="totals-row is-total"><span>Total</span><strong>' + money(invoice.total_formatted, invoice.total_amount) + '</strong></div>' +
                    '<div class="totals-row"><span>Paid</span><strong style="color:var(--success)">' + money(invoice.paid_formatted, invoice.paid) + '</strong></div>' +
                    (settled
                        ? '<div class="totals-row"><span>Balance</span><strong><span class="badge badge-success">Settled</span></strong></div>'
                        : '<div class="totals-row is-total"><span>Balance due</span><strong>' + money(invoice.remaining_formatted, invoice.remaining_cents / 100) + '</strong></div>') +
                '</div>' +
                (invoice.notes ? '<p class="text-sm text-muted mt-6">' + UI.escape(invoice.notes) + '</p>' : '') +
            '</div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function load() {
        if (!id) {
            root.innerHTML = Site.empty('Invoice not found', 'The link may be broken.');
            return;
        }
        API.get('/portal/invoices/' + id).then(function (data) {
            render(data || {});
        }).catch(function (error) {
            root.innerHTML = Site.empty('Invoice not found', error.message || 'This invoice is unavailable.');
        });
    }

    var printButton = UI.qs('[data-print]');
    if (printButton) {
        printButton.addEventListener('click', function () {
            window.print();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
