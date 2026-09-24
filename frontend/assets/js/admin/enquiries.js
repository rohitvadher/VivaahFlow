(function () {
    'use strict';

    var records = {};
    var current = null;
    var tableEl = document.getElementById('enquiries-table');
    var modal = document.getElementById('enquiry-modal');
    var body = modal.querySelector('[data-enquiry-body]');

    var STATUSES = ['new', 'contacted', 'quotation_pending', 'quotation_sent', 'converted', 'closed'];

    function rowActions(row) {
        return '<button type="button" class="btn-icon" data-action="view" data-id="' + row.id + '" title="View"><i data-lucide="eye"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'enquiries-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/enquiries',
        perPage: 10,
        emptyText: 'No enquiries yet',
        emptyHint: 'New enquiries from the website will appear here.',
        columns: [
            { label: 'Reference', render: function (row) { return '<span class="font-semibold">' + UI.escape(row.reference_no) + '</span>'; } },
            {
                label: 'Customer',
                render: function (row) {
                    return '<div class="font-medium">' + UI.escape(row.customer_name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.customer_email || row.customer_phone || '') + '</div>';
                }
            },
            {
                label: 'Event',
                render: function (row) {
                    return UI.escape(row.event_type || '\u2014') + '<div class="text-muted text-sm">' + UI.escape(window.APP.formatDate(row.event_date)) + '</div>';
                }
            },
            { label: 'Venue', render: function (row) { return UI.escape(row.venue_address || '\u2014'); } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } },
            { label: 'Received', render: function (row) { return UI.escape(window.APP.formatDate(row.created_at)); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' enquir' + (data.pagination.total === 1 ? 'y' : 'ies');
            }
        }
    });

    function renderDetail(enquiry) {
        current = enquiry;
        UI.qs('[data-enquiry-ref]').textContent = enquiry.reference_no;
        UI.qs('[data-enquiry-customer]').textContent = enquiry.customer_name + (enquiry.customer_email ? ' \u00B7 ' + enquiry.customer_email : '');

        var statusOptions = STATUSES.map(function (status) {
            return '<option value="' + status + '"' + (status === enquiry.status ? ' selected' : '') + '>' + window.APP.titleCase(status) + '</option>';
        }).join('');

        var items = enquiry.items || [];
        var itemsHtml = items.length
            ? '<div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Unit Price</th></tr></thead><tbody>' +
                items.map(function (item) {
                    var name = item.service_name || item.package_name || item.source_type;
                    return '<tr><td>' + UI.escape(name) + '</td><td>' + UI.escape(window.APP.titleCase(item.source_type || '')) + '</td>' +
                        '<td>' + window.APP.number(item.quantity || 1) + '</td>' +
                        '<td>' + UI.escape(window.APP.money(Math.round(Number(item.unit_price || 0) * 100))) + '</td></tr>';
                }).join('') + '</tbody></table></div>'
            : '<div class="table-empty"><div class="empty-title">No services selected</div></div>';

        body.innerHTML =
            '<div class="detail-list mb-5">' +
            '<div class="detail-row"><dt>Phone</dt><dd>' + UI.escape(enquiry.customer_phone || '\u2014') + '</dd></div>' +
            '<div class="detail-row"><dt>Event date</dt><dd>' + UI.escape(window.APP.formatDate(enquiry.event_date)) + '</dd></div>' +
            '<div class="detail-row"><dt>Event type</dt><dd>' + UI.escape(enquiry.event_type || '\u2014') + '</dd></div>' +
            '<div class="detail-row"><dt>Venue</dt><dd>' + UI.escape(enquiry.venue_address || '\u2014') + '</dd></div>' +
            '<div class="detail-row"><dt>Notes</dt><dd>' + UI.escape(enquiry.notes || '\u2014') + '</dd></div>' +
            '</div>' +
            '<div class="mb-4"><label class="label">Status</label>' +
            '<div class="flex gap-2"><select class="form-control" data-enquiry-status>' + statusOptions + '</select>' +
            '<button type="button" class="btn btn-soft" data-save-enquiry-status data-permission="enquiries.update">Update</button></div></div>' +
            '<div class="card"><div class="card-head"><div class="card-title" style="font-size:14px">Requested Services</div></div>' + itemsHtml + '</div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function openDetail(id) {
        Loader.skeleton(body, { rows: 2 });
        UI.openModal(modal);
        API.get('/enquiries/' + id).then(renderDetail).catch(function (error) { Alerts.error(error.message); });
    }

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        if (trigger.getAttribute('data-action') === 'view') {
            openDetail(Number(trigger.getAttribute('data-id')));
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target.closest('[data-save-enquiry-status]')) {
            var status = modal.querySelector('[data-enquiry-status]').value;
            API.patch('/enquiries/' + current.id + '/status', { status: status }).then(function () {
                Alerts.success('Enquiry status updated.');
                table.reload();
                UI.closeModal(modal);
            }).catch(function (error) { Alerts.error(error.message); });
        }
    });

    modal.querySelector('[data-enquiry-quotation]').addEventListener('click', function () {
        if (!current) {
            return;
        }
        Alerts.confirm({ title: 'Create quotation?', text: 'A draft quotation will be generated from this enquiry.', confirmText: 'Create' }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }
            API.post('/enquiries/' + current.id + '/quotation').then(function (data) {
                Alerts.success('Quotation created.');
                window.location.href = window.APP.route('manage.quotation', { id: data.id });
            }).catch(function (error) { Alerts.error(error.message); });
        });
    });

    modal.querySelector('[data-enquiry-lead]').addEventListener('click', function () {
        if (!current) {
            return;
        }
        API.post('/enquiries/' + current.id + '/lead').then(function () {
            Alerts.success('Lead created.');
            window.location.href = window.APP.route('manage.leads');
        }).catch(function (error) { Alerts.error(error.message); });
    });
})();
