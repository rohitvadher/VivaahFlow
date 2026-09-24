(function () {
    'use strict';

    var invoiceId = Number(window.APP.recordId() || 0);
    var container = document.getElementById('invoice-detail');
    var numberEl = document.querySelector('[data-invoice-number]');
    var subtitleEl = document.querySelector('[data-invoice-subtitle]');
    var actionsEl = document.querySelector('[data-invoice-actions]');
    var current = null;

    function applyPerms(scope) {
        if (window.APP.currentUser) {
            UI.applyPermissions(scope);
        } else {
            document.addEventListener('app:ready', function () { UI.applyPermissions(scope); }, { once: true });
        }
    }

    function renderActions(data) {
        var html = '<button type="button" class="btn btn-outline" data-action="print"><i data-lucide="printer"></i> Print</button>';
        var status = data.computed_status || data.status;
        if (status === 'draft') {
            html += '<button type="button" class="btn btn-primary" data-action="status" data-status="issued" data-permission="invoices.update"><i data-lucide="send"></i> Mark Issued</button>';
        } else if (status === 'issued') {
            html += '<button type="button" class="btn btn-outline" data-action="status" data-status="draft" data-permission="invoices.update"><i data-lucide="file-text"></i> Move to Draft</button>';
        }
        if (data.status !== 'cancelled') {
            html += '<button type="button" class="btn btn-outline" data-action="status" data-status="cancelled" data-permission="invoices.update"><i data-lucide="circle-x"></i> Cancel</button>';
        }
        actionsEl.innerHTML = html;
        applyPerms(actionsEl);
    }

    function render(data) {
        current = data;
        numberEl.textContent = data.invoice_number;
        subtitleEl.textContent = data.customer_name + ' \u00B7 ' + data.booking_no + ' \u00B7 ' + data.total_formatted;
        renderActions(data);

        var serviceRows = (data.services || []).map(function (item) {
            return '<tr><td><div class="font-semibold">' + UI.escape(item.item_name) + '</div></td>' +
                '<td>' + window.APP.number(item.quantity) + '</td>' +
                '<td>' + Workflow.amount(item.unit_price) + '</td>' +
                '<td class="font-semibold">' + Workflow.amount(item.amount) + '</td></tr>';
        }).join('');

        container.innerHTML =
            '<div class="grid-stats">' +
            '<div class="stat-card"><div class="stat-label">Total</div><div class="stat-value">' + UI.escape(data.total_formatted) + '</div></div>' +
            '<div class="stat-card"><div class="stat-label">Paid</div><div class="stat-value">' + UI.escape(data.paid_formatted) + '</div></div>' +
            (Number(data.remaining_cents) === 0
                ? '<div class="stat-card"><div class="stat-label">Balance</div><div class="stat-value"><span class="badge badge-success">Settled</span></div></div>'
                : '<div class="stat-card"><div class="stat-label">Balance</div><div class="stat-value">' + UI.escape(data.remaining_formatted) + '</div><div class="text-muted text-sm mt-1">Status: ' + UI.escape(window.APP.titleCase(data.computed_status || data.status)) + '</div></div>') +
            '</div>' +
            '<div class="grid-3 mt-4">' +
            '<div class="grid-span-2 card">' +
            '<div class="card-head"><h2 class="card-title">Line Items</h2></div>' +
            '<div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr></thead><tbody>' + serviceRows + '</tbody></table></div>' +
            '<div class="card-pad">' + summaryHtml(data) + '</div>' +
            '</div>' +
            '<div class="flex flex-col gap-4">' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Details</h2></div><div class="card-pad"><dl class="detail-list">' +
            Workflow.detailRow('Invoice', UI.escape(data.invoice_number)) +
            Workflow.detailRow('Booking', '<a class="link-brand" href="' + window.APP.route('manage.booking', { id: data.booking_id }) + '">' + UI.escape(data.booking_no) + '</a>') +
            Workflow.detailRow('Issue date', UI.escape(window.APP.formatDate(data.issue_date))) +
            Workflow.detailRow('Due date', UI.escape(window.APP.formatDate(data.due_date))) +
            Workflow.detailRow('Status', UI.statusBadge(data.computed_status || data.status)) +
            '</dl></div></div>' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Customer</h2></div><div class="card-pad"><dl class="detail-list">' +
            Workflow.detailRow('Name', UI.escape(data.customer_name)) +
            Workflow.detailRow('Email', UI.escape(data.customer_email || '\u2014')) +
            Workflow.detailRow('Phone', UI.escape(data.customer_phone || '\u2014')) +
            Workflow.detailRow('Address', UI.escape(data.customer_address || '\u2014')) +
            '</dl></div></div>' +
            (data.notes ? '<div class="card"><div class="card-head"><h2 class="card-title">Notes</h2></div><div class="card-pad text-muted text-sm">' + UI.escape(data.notes) + '</div></div>' : '') +
            '</div></div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function summaryHtml(data) {
        var rows = Workflow.summaryRows(data);
        return '<div class="totals-list">' + rows.map(function (row) {
            return '<div class="totals-row' + (row[0] === 'Total' ? ' is-total' : '') + '"><span>' + UI.escape(row[0]) + '</span><span>' + UI.escape(row[1]) + '</span></div>';
        }).join('') +
            '<div class="totals-row"><span>Paid</span><span>' + UI.escape(data.paid_formatted) + '</span></div>' +
            (Number(data.remaining_cents) > 0 ? '<div class="totals-row"><span>Balance due</span><span>' + UI.escape(data.remaining_formatted) + '</span></div>' : '') +
            '</div>';
    }

    function load() {
        Loader.skeleton(container, { rows: 2 });
        API.get('/invoices/' + invoiceId).then(render).catch(function (error) {
            container.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">' + UI.escape(error.message) + '</div><a class="btn btn-outline mt-3" href="' + window.APP.route('manage.invoices') + '">Back to invoices</a></div></div>';
        });
    }

    actionsEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger || !current) {
            return;
        }
        var action = trigger.getAttribute('data-action');
        if (action === 'print') {
            window.print();
        } else if (action === 'status') {
            var status = trigger.getAttribute('data-status');
            API.patch('/invoices/' + current.id + '/status', { status: status }).then(function () {
                Alerts.success('Invoice status updated.');
                load();
            }).catch(function (error) { Alerts.error(error.message); });
        }
    });

    if (invoiceId) {
        load();
    } else {
        container.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">No invoice selected</div></div></div>';
    }
})();
