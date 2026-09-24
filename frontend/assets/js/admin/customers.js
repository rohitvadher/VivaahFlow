(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('customers-table');
    var form = document.getElementById('customer-form');
    var modal = document.getElementById('customer-modal');
    var submitBtn = form.querySelector('[data-submit]');
    var isEdit = false;

    function openForm(record) {
        UI.clear(form);
        isEdit = !!record;
        UI.qs('[data-modal-title]', modal).textContent = isEdit ? 'Edit Customer' : 'Add Customer';
        if (isEdit) {
            UI.fill(form, record);
        }
        UI.openModal(modal);
    }

    function rowActions(row) {
        return '' +
            '<button type="button" class="btn-icon" data-action="view" data-id="' + row.id + '" title="View"><i data-lucide="eye"></i></button>' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="customers.update" title="Edit"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="toggle" data-id="' + row.id + '" data-permission="customers.update" title="Toggle status"><i data-lucide="' + (row.status === 'active' ? 'toggle-right' : 'toggle-left') + '"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="customers.delete" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'customers-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/customers',
        perPage: 10,
        emptyText: 'No customers yet',
        emptyHint: 'Add your first customer to get started.',
        columns: [
            {
                label: 'Customer',
                render: function (row) {
                    return '<div class="flex items-center gap-3">' +
                        '<span class="avatar">' + UI.escape(window.APP.initials(row.name)) + '</span>' +
                        '<div class="min-w-0"><div class="font-semibold truncate">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm truncate">' + UI.escape(row.email || '\u2014') + '</div></div></div>';
                }
            },
            { label: 'Phone', render: function (row) { return UI.escape(row.phone || '\u2014'); } },
            { label: 'Wedding Date', render: function (row) { return UI.escape(row.wedding_date_label || window.APP.formatDate(row.wedding_date)); } },
            { label: 'City', render: function (row) { return UI.escape(row.city || '\u2014'); } },
            { label: 'Source', render: function (row) { return UI.escape(window.APP.titleCase(row.source || 'manual')); } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } },
            { label: 'Added', render: function (row) { return UI.escape(window.APP.formatDate(row.created_at)); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' customer' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    document.querySelectorAll('[data-open-customer]').forEach(function (button) {
        button.addEventListener('click', function () { openForm(null); });
    });

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var id = Number(trigger.getAttribute('data-id'));
        var record = records[id];
        var action = trigger.getAttribute('data-action');

        if (action === 'view') {
            window.location.href = window.APP.route('manage.customer', { id: id });
            return;
        }
        if (action === 'edit' && record) {
            openForm(record);
            return;
        }
        if (action === 'toggle' && record) {
            var next = record.status === 'active' ? 'inactive' : 'active';
            API.patch('/customers/' + id + '/status', { status: next }).then(function () {
                Alerts.success('Customer marked as ' + next + '.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
            return;
        }
        if (action === 'delete' && record) {
            Alerts.confirm({
                title: 'Delete customer?',
                text: 'This will remove ' + record.name + ' from your directory.',
                confirmText: 'Delete',
                danger: true
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.del('/customers/' + id).then(function () {
                    Alerts.success('Customer deleted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(form);
        var id = payload.id;
        delete payload.id;
        UI.setLoading(submitBtn, true);
        var request = isEdit ? API.put('/customers/' + id, payload) : API.post('/customers', payload);
        request.then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success(isEdit ? 'Customer updated.' : 'Customer created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });
})();
