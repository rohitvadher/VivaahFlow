(function () {
    'use strict';

    var root = UI.qs('[data-quotation]');
    if (!root) {
        return;
    }

    var id = Number(root.getAttribute('data-id'));
    var current = null;

    function actions(quotation) {
        if (quotation.status === 'accepted') {
            return '<div class="notice notice-success">You have accepted this quotation. Our team will confirm your booking shortly.</div>';
        }
        if (quotation.status === 'rejected') {
            return '<div class="notice notice-danger">You rejected this quotation.</div>';
        }
        if (quotation.is_expired) {
            return '<div class="notice notice-warning">This quotation has expired. Please contact us for an updated proposal.</div>';
        }
        if (quotation.status !== 'sent') {
            return '<div class="notice">This quotation is still being prepared.</div>';
        }
        return '' +
            '<div class="flex flex-wrap gap-3">' +
                '<button type="button" class="btn btn-success" data-accept>' + UI.icon('check') + ' Accept Quotation</button>' +
                '<button type="button" class="btn btn-outline" data-reject>' + UI.icon('x') + ' Decline</button>' +
            '</div>';
    }

    function render(quotation) {
        current = quotation;
        if (quotation.status !== 'sent' || quotation.is_expired) {
            var modal = document.getElementById('reject-quotation-modal');
            if (modal) {
                modal.remove();
            }
        }
        var items = quotation.items || [];
        var discount = Number(quotation.discount_amount);
        root.innerHTML = '' +
            '<div class="grid gap-6 lg:grid-cols-3">' +
                '<div class="lg:col-span-2 grid gap-6">' +
                    '<div class="card">' +
                        '<div class="card-head">' +
                            '<div>' +
                                '<h2 class="card-title">' + UI.escape(quotation.reference_no) + '</h2>' +
                                '<p class="card-subtitle">' + (quotation.valid_until ? 'Valid until ' + APP.formatDate(quotation.valid_until) : '') + '</p>' +
                            '</div>' +
                            UI.statusBadge(quotation.status) +
                        '</div>' +
                        (items.length > 0
                            ? '<div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th style="text-align:right">Unit price</th><th style="text-align:right">Amount</th></tr></thead><tbody>' +
                                items.map(function (item) {
                                    return '<tr>' +
                                        '<td>' + UI.escape(item.item_name) + (item.notes ? '<div class="text-xs text-muted">' + UI.escape(item.notes) + '</div>' : '') + '</td>' +
                                        '<td>' + Number(item.quantity || 1) + '</td>' +
                                        '<td style="text-align:right">' + Site.money(item.unit_price) + '</td>' +
                                        '<td style="text-align:right" class="font-medium">' + Site.money(item.amount) + '</td>' +
                                    '</tr>';
                                }).join('') + '</tbody></table></div>'
                            : '<div class="table-empty"><div class="empty-title">No items</div></div>') +
                    '</div>' +
                    (quotation.notes ? '<div class="card card-pad"><h3 class="card-title mb-2">Notes from our team</h3><p class="text-sm text-muted">' + UI.escape(quotation.notes) + '</p></div>' : '') +
                '</div>' +
                '<aside class="grid gap-6" style="align-content:start">' +
                    '<div class="card card-pad">' +
                        '<div class="totals-list">' +
                            '<div class="totals-row"><span>Subtotal</span><strong>' + UI.escape(quotation.subtotal_formatted || Site.money(quotation.subtotal)) + '</strong></div>' +
                            (discount > 0 ? '<div class="totals-row"><span>Discount</span><strong style="color:var(--success)">\u2212 ' + UI.escape(quotation.discount_formatted || Site.money(quotation.discount_amount)) + '</strong></div>' : '') +
                            '<div class="totals-row is-total"><span>Total</span><strong>' + UI.escape(quotation.total_formatted || Site.money(quotation.total_amount)) + '</strong></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="card card-pad" data-actions>' + actions(quotation) + '</div>' +
                '</aside>' +
            '</div>';

        wire(quotation);
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function wire(quotation) {
        var accept = root.querySelector('[data-accept]');
        if (accept) {
            accept.addEventListener('click', function () {
                Alerts.confirm({
                    title: 'Accept quotation?',
                    text: 'This will confirm ' + quotation.reference_no + '. Our team will then finalise your booking.',
                    confirmText: 'Accept'
                }).then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    API.post('/portal/quotations/' + id + '/accept', {}).then(function () {
                        Alerts.success('Quotation accepted. Thank you!');
                        load();
                    }).catch(function (error) {
                        Alerts.error(error.message || 'Unable to accept the quotation.');
                    });
                });
            });
        }
        var reject = root.querySelector('[data-reject]');
        if (reject) {
            reject.addEventListener('click', function () {
                UI.openModal('reject-quotation-modal');
            });
        }
        var rejectConfirm = document.querySelector('[data-reject-confirm]');
        if (rejectConfirm) {
            rejectConfirm.addEventListener('click', function () {
                var reason = UI.qs('#reject-reason');
                UI.setLoading(rejectConfirm, true);
                API.post('/portal/quotations/' + id + '/reject', { reason: reason ? reason.value.trim() : '' }).then(function () {
                    UI.setLoading(rejectConfirm, false);
                    UI.closeModal('reject-quotation-modal');
                    Alerts.success('Quotation declined.');
                    load();
                }).catch(function (error) {
                    UI.setLoading(rejectConfirm, false);
                    Alerts.error(error.message || 'Unable to decline the quotation.');
                });
            });
        }
    }

    function load() {
        Loader.skeleton(root, { rows: 1, height: 220 });
        if (!id) {
            root.innerHTML = Site.empty('Quotation not found', 'The link may be broken.');
            return;
        }
        API.get('/portal/quotations/' + id).then(function (data) {
            render(data || {});
        }).catch(function (error) {
            root.innerHTML = Site.empty('Quotation not found', error.message || 'This quotation is unavailable.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
