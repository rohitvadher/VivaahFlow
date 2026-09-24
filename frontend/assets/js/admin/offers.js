(function () {
    'use strict';

    var records = {};
    var services = [];
    var packages = [];
    var tableEl = document.getElementById('offers-table');
    var modal = document.getElementById('offer-modal');
    var form = document.getElementById('offer-form');
    var submitBtn = form.querySelector('[data-submit]');
    var isEdit = false;

    function checkboxList(items, selectedIds, attribute) {
        if (!items.length) {
            return '<div class="text-muted text-sm">Nothing available.</div>';
        }
        return items.map(function (item) {
            var checked = selectedIds.indexOf(Number(item.id)) !== -1;
            return '<label class="checkbox" style="display:flex;margin-bottom:8px">' +
                '<input type="checkbox" value="' + item.id + '" ' + attribute + (checked ? ' checked' : '') + '>' +
                '<span>' + UI.escape(item.name) + '</span></label>';
        }).join('');
    }

    function syncScope() {
        var scope = form.querySelector('[data-applicable]').value;
        form.querySelector('[data-scope-services]').classList.toggle('hidden', scope !== 'services');
        form.querySelector('[data-scope-packages]').classList.toggle('hidden', scope !== 'packages');
    }

    function rowActions(row) {
        return '' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="offers.update" title="Edit"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="toggle" data-id="' + row.id + '" data-permission="offers.update" title="Toggle status"><i data-lucide="' + (row.status === 'active' ? 'toggle-right' : 'toggle-left') + '"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="offers.delete" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'offers-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/offers',
        perPage: 10,
        emptyText: 'No offers yet',
        emptyHint: 'Create a promotional offer to attract customers.',
        columns: [
            {
                label: 'Offer',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(window.APP.titleCase(row.applicable_to)) + '</div>';
                }
            },
            { label: 'Discount', render: function (row) { return '<span class="badge badge-warning">' + UI.escape(row.discount_label) + '</span>'; } },
            { label: 'Validity', render: function (row) { return UI.escape(window.APP.formatDate(row.start_date)) + ' \u2192 ' + UI.escape(window.APP.formatDate(row.end_date)); } },
            { label: 'Live', render: function (row) { return Number(row.is_live) ? '<span class="badge badge-success">Live</span>' : '<span class="badge badge-muted">Not live</span>'; } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' offer' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openForm(record) {
        UI.clear(form);
        form.querySelector('[data-service-list]').innerHTML = checkboxList(services, [], 'data-service-checkbox');
        form.querySelector('[data-package-list]').innerHTML = checkboxList(packages, [], 'data-package-checkbox');
        isEdit = !!record;
        UI.qs('[data-modal-title]', modal).textContent = isEdit ? 'Edit Offer' : 'Add Offer';
        if (record) {
            UI.fill(form, record);
        }
        syncScope();
        UI.openModal(modal);

        if (record) {
            API.get('/offers/' + record.id).then(function (data) {
                form.querySelector('[data-service-list]').innerHTML = checkboxList(services, (data.service_ids || []).map(Number), 'data-service-checkbox');
                form.querySelector('[data-package-list]').innerHTML = checkboxList(packages, (data.package_ids || []).map(Number), 'data-package-checkbox');
                syncScope();
            }).catch(function (error) { Alerts.error(error.message); });
        }
    }

    form.querySelector('[data-applicable]').addEventListener('change', syncScope);

    document.querySelectorAll('[data-open-offer]').forEach(function (button) {
        button.addEventListener('click', function () { openForm(null); });
    });

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var id = Number(trigger.getAttribute('data-id'));
        var action = trigger.getAttribute('data-action');
        if (action === 'edit' && records[id]) {
            openForm(records[id]);
        } else if (action === 'toggle' && records[id]) {
            var next = records[id].status === 'active' ? 'inactive' : 'active';
            API.patch('/offers/' + id + '/status', { status: next }).then(function () {
                Alerts.success('Offer marked as ' + next + '.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        } else if (action === 'delete' && records[id]) {
            Alerts.confirm({ title: 'Delete offer?', text: 'This cannot be undone.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.del('/offers/' + id).then(function () {
                    Alerts.success('Offer deleted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var data = UI.serialize(form);
        data.service_ids = UI.qsa('[data-service-checkbox]:checked', form).map(function (box) { return Number(box.value); });
        data.package_ids = UI.qsa('[data-package-checkbox]:checked', form).map(function (box) { return Number(box.value); });
        var id = data.id;
        delete data.id;
        UI.setLoading(submitBtn, true);
        var request = isEdit ? API.put('/offers/' + id, data) : API.post('/offers', data);
        request.then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success(isEdit ? 'Offer updated.' : 'Offer created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    Promise.all([
        API.get('/services', { per_page: 100 }),
        API.get('/packages', { per_page: 100 })
    ]).then(function (results) {
        services = (results[0] && results[0].items) ? results[0].items : [];
        packages = (results[1] && results[1].items) ? results[1].items : [];
        var keepServices = UI.qsa('[data-service-checkbox]:checked', form).map(function (box) { return Number(box.value); });
        var keepPackages = UI.qsa('[data-package-checkbox]:checked', form).map(function (box) { return Number(box.value); });
        form.querySelector('[data-service-list]').innerHTML = checkboxList(services, keepServices, 'data-service-checkbox');
        form.querySelector('[data-package-list]').innerHTML = checkboxList(packages, keepPackages, 'data-package-checkbox');
    }).catch(function (error) { Alerts.error(error.message); });
})();
