(function () {
    'use strict';

    var quotationId = Number(window.APP.recordId() || 0);
    var container = document.getElementById('quotation-detail');
    var actionsEl = document.querySelector('[data-quotation-actions]');
    var referenceEl = document.querySelector('[data-quotation-reference]');
    var subtitleEl = document.querySelector('[data-quotation-subtitle]');
    var editModal = document.getElementById('quotation-edit-modal');
    var editForm = document.getElementById('quotation-edit-form');
    var itemsEditor = editForm.querySelector('[data-items-editor]');
    var catalogueSelect = editForm.querySelector('[data-catalogue-select]');
    var editSubmit = editForm.querySelector('[data-submit]');
    var bookingModal = document.getElementById('booking-modal');
    var bookingForm = document.getElementById('booking-form');
    var bookingSubmit = bookingForm.querySelector('[data-submit]');
    var catalogue = [];
    var current = null;

    function applyPerms(scope) {
        if (window.APP.currentUser) {
            UI.applyPermissions(scope);
        } else {
            document.addEventListener('app:ready', function () { UI.applyPermissions(scope); }, { once: true });
        }
    }

    function summaryHtml(data) {
        return '<div class="totals-list">' + Workflow.summaryRows(data).map(function (row) {
            var last = row[0] === 'Total';
            return '<div class="totals-row' + (last ? ' is-total' : '') + '"><span>' + UI.escape(row[0]) + '</span><span>' + UI.escape(row[1]) + '</span></div>';
        }).join('') + '</div>';
    }

    function renderActions(data) {
        var html = '';
        if ((data.status === 'draft' || data.status === 'sent') && UI.escape) {
            html += '<button type="button" class="btn btn-outline" data-action="edit" data-permission="quotations.update"><i data-lucide="pencil"></i> Edit</button>';
        }
        if (data.status === 'draft') {
            html += '<button type="button" class="btn btn-outline" data-action="send" data-permission="quotations.status"><i data-lucide="send"></i> Mark Sent</button>';
        }
        if (Workflow.quotationCanAccept(data)) {
            html += '<button type="button" class="btn btn-primary" data-action="accept" data-permission="quotations.status"><i data-lucide="check"></i> Accept</button>';
            html += '<button type="button" class="btn btn-outline" data-action="reject" data-permission="quotations.status"><i data-lucide="x"></i> Reject</button>';
        }
        if (data.status === 'accepted') {
            html += '<button type="button" class="btn btn-primary" data-action="booking" data-permission="bookings.create"><i data-lucide="calendar-plus"></i> Create Booking</button>';
        }
        actionsEl.innerHTML = html;
        applyPerms(actionsEl);
    }

    function render(data) {
        current = data;
        referenceEl.textContent = data.reference_no;
        subtitleEl.textContent = data.customer_name + ' \u00B7 ' + window.APP.titleCase(data.status) + ' \u00B7 ' + data.total_formatted;
        renderActions(data);

        var itemsRows = (data.items || []).map(function (item) {
            return '<tr>' +
                '<td><div class="font-semibold">' + UI.escape(item.item_name) + '</div>' +
                (item.notes ? '<div class="text-muted text-sm">' + UI.escape(item.notes) + '</div>' : '') + '</td>' +
                '<td>' + window.APP.number(item.quantity) + '</td>' +
                '<td>' + Workflow.amount(item.unit_price) + '</td>' +
                '<td class="font-semibold">' + Workflow.amount(item.amount) + '</td>' +
                '</tr>';
        }).join('');

        var expiryNote = '';
        if (data.valid_until) {
            var expired = data.is_expired || data.status === 'expired';
            expiryNote = Workflow.detailRow('Valid until', '<span' + (expired ? ' style="color:var(--danger)"' : '') + '>' + UI.escape(window.APP.formatDate(data.valid_until)) + (expired ? ' \u00B7 expired' : '') + '</span>');
        }

        container.innerHTML =
            '<div class="grid-3">' +
            '<div class="card grid-span-2">' +
            '<div class="card-head"><h2 class="card-title">Quotation Items</h2><span class="badge badge-plain">' + window.APP.number((data.items || []).length) + ' items</span></div>' +
            '<div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr></thead><tbody>' + itemsRows + '</tbody></table></div>' +
            '<div class="card-pad">' + summaryHtml(data) + '</div>' +
            '</div>' +
            '<div class="flex flex-col gap-4">' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Customer</h2></div><div class="card-pad"><dl class="detail-list">' +
            Workflow.detailRow('Name', UI.escape(data.customer_name)) +
            Workflow.detailRow('Email', UI.escape(data.customer_email || '\u2014')) +
            Workflow.detailRow('Phone', UI.escape(data.customer_phone || '\u2014')) +
            Workflow.detailRow('Enquiry', UI.escape(data.enquiry_no || '\u2014')) +
            expiryNote +
            Workflow.detailRow('Status', UI.statusBadge(data.is_expired ? 'expired' : data.status)) +
            '</dl></div></div>' +
            (data.notes ? '<div class="card"><div class="card-head"><h2 class="card-title">Notes</h2></div><div class="card-pad text-muted text-sm">' + UI.escape(data.notes) + '</div></div>' : '') +
            '</div></div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function itemRow(item) {
        item = item || { item_name: '', quantity: 1, unit_price: 0, source_type: 'service', source_id: '' };
        var row = document.createElement('div');
        row.className = 'item-row';
        row.setAttribute('data-item-row', '');
        row.innerHTML =
            '<input type="hidden" data-item="source_type" value="' + UI.escape(item.source_type || 'service') + '">' +
            '<input type="hidden" data-item="source_id" value="' + UI.escape(item.source_id || '') + '">' +
            '<input class="form-control" data-item="item_name" placeholder="Item name" value="' + UI.escape(item.item_name) + '">' +
            '<input class="form-control" data-item="quantity" type="number" min="1" step="1" value="' + UI.escape(item.quantity || 1) + '" style="max-width:90px">' +
            '<input class="form-control" data-item="unit_price" type="number" min="0" step="0.01" value="' + UI.escape(item.unit_price || 0) + '" style="max-width:140px">' +
            '<button type="button" class="btn-icon" data-remove-item title="Remove" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
        itemsEditor.appendChild(row);
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function openEdit() {
        if (!current) {
            return;
        }
        itemsEditor.innerHTML = '';
        (current.items || []).forEach(itemRow);
        editForm.discount_type.value = current.discount_type || '';
        editForm.discount_value.value = current.discount_value || 0;
        editForm.valid_until.value = current.valid_until || '';
        editForm.notes.value = current.notes || '';
        UI.openModal(editModal);
    }

    document.addEventListener('click', function (event) {
        var action = event.target.closest('[data-action]');
        if (action && current) {
            var name = action.getAttribute('data-action');
            if (name === 'edit') {
                openEdit();
            } else if (name === 'send') {
                API.patch('/quotations/' + current.id + '/status', { status: 'sent' }).then(function () {
                    Alerts.success('Quotation marked as sent.');
                    load();
                }).catch(function (error) { Alerts.error(error.message); });
            } else if (name === 'accept') {
                Alerts.confirm({ title: 'Accept quotation?', text: 'This unlocks booking creation.', confirmText: 'Accept' }).then(function (confirmed) {
                    if (!confirmed) { return; }
                    API.post('/quotations/' + current.id + '/accept').then(function () {
                        Alerts.success('Quotation accepted.');
                        load();
                    }).catch(function (error) { Alerts.error(error.message); });
                });
            } else if (name === 'reject') {
                Alerts.confirm({ title: 'Reject quotation?', text: 'The customer will see it as rejected.', confirmText: 'Reject', danger: true }).then(function (confirmed) {
                    if (!confirmed) { return; }
                    API.post('/quotations/' + current.id + '/reject').then(function () {
                        Alerts.success('Quotation rejected.');
                        load();
                    }).catch(function (error) { Alerts.error(error.message); });
                });
            } else if (name === 'booking') {
                bookingForm.reset();
                bookingForm.event_date.value = current.valid_until || '';
                UI.openModal(bookingModal);
            }
            return;
        }

        var remove = event.target.closest('[data-remove-item]');
        if (remove) {
            remove.closest('[data-item-row]').remove();
        }
    });

    editForm.querySelector('[data-add-item]').addEventListener('click', function () {
        var option = catalogueSelect.value;
        if (!option) {
            itemRow(null);
            return;
        }
        var parts = option.split(':');
        var source = catalogue.filter(function (entry) { return entry.type === parts[0] && Number(entry.id) === Number(parts[1]); })[0];
        itemRow(source ? { item_name: source.name, quantity: 1, unit_price: source.price, source_type: source.type, source_id: source.id } : null);
    });

    editForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var items = UI.qsa('[data-item-row]', itemsEditor).map(function (row) {
            return {
                source_type: UI.qs('[data-item="source_type"]', row).value || 'service',
                source_id: UI.qs('[data-item="source_id"]', row).value ? Number(UI.qs('[data-item="source_id"]', row).value) : null,
                item_name: UI.qs('[data-item="item_name"]', row).value.trim(),
                quantity: Number(UI.qs('[data-item="quantity"]', row).value) || 1,
                unit_price: Number(UI.qs('[data-item="unit_price"]', row).value) || 0
            };
        });
        if (!items.length) {
            Alerts.warning('Add at least one item before saving.');
            return;
        }
        var payload = {
            discount_type: editForm.discount_type.value || null,
            discount_value: Number(editForm.discount_value.value) || 0,
            valid_until: editForm.valid_until.value || null,
            notes: editForm.notes.value,
            items: items
        };
        UI.setLoading(editSubmit, true);
        API.post('/quotations/' + current.id, payload).then(function () {
            UI.setLoading(editSubmit, false);
            UI.closeModal(editModal);
            Alerts.success('Quotation updated.');
            load();
        }).catch(function (error) {
            UI.setLoading(editSubmit, false);
            Alerts.error(error.message);
        });
    });

    bookingForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(bookingForm);
        UI.setLoading(bookingSubmit, true);
        API.post('/bookings', Object.assign({ quotation_id: current.id }, payload)).then(function (booking) {
            UI.setLoading(bookingSubmit, false);
            UI.closeModal(bookingModal);
            Alerts.success('Booking ' + booking.reference_no + ' created.');
            window.setTimeout(function () {
                window.location.href = window.APP.route('manage.booking', { id: booking.id });
            }, 400);
        }).catch(function (error) {
            UI.setLoading(bookingSubmit, false);
            if (error.errors) {
                UI.showErrors(bookingForm, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    function load() {
        Loader.skeleton(container, { rows: 2 });
        API.get('/quotations/' + quotationId).then(render).catch(function (error) {
            container.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">' + UI.escape(error.message) + '</div><a class="btn btn-outline mt-3" href="' + window.APP.route('manage.quotations') + '">Back to quotations</a></div></div>';
        });
    }

    Promise.all([
        API.get('/services', { per_page: 100 }),
        API.get('/packages', { per_page: 100 })
    ]).then(function (results) {
        var services = (results[0] && results[0].items) || [];
        var packages = (results[1] && results[1].items) || [];
        services.forEach(function (service) {
            catalogue.push({ type: 'service', id: service.id, name: service.name, price: Number(service.starting_price) });
        });
        packages.forEach(function (pkg) {
            catalogue.push({ type: 'package', id: pkg.id, name: pkg.name + ' (Package)', price: Number(pkg.total_amount) });
        });
        catalogueSelect.innerHTML = '<option value="">Add from catalogue</option>' + catalogue.map(function (entry) {
            return '<option value="' + entry.type + ':' + entry.id + '">' + UI.escape(entry.name) + ' \u00B7 ' + Workflow.amount(entry.price) + '</option>';
        }).join('');
    }).catch(function () {
        catalogueSelect.innerHTML = '<option value="">Add from catalogue</option>';
    });

    if (quotationId) {
        load();
    } else {
        container.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">No quotation selected</div></div></div>';
    }
})();
