(function () {
    'use strict';

    var records = {};
    var services = [];
    var tableEl = document.getElementById('packages-table');
    var modal = document.getElementById('package-modal');
    var form = document.getElementById('package-form');
    var submitBtn = form.querySelector('[data-submit]');
    var isEdit = false;
    var currentServices = [];

    function imageUrl(path) {
        return path ? window.APP.baseUrl + '/' + path : '';
    }

    function renderServiceList(selectedIds) {
        var host = form.querySelector('[data-service-list]');
        if (!services.length) {
            host.innerHTML = '<div class="text-muted text-sm">No services available. Add services first.</div>';
            return;
        }
        host.innerHTML = services.map(function (service) {
            var checked = selectedIds.indexOf(Number(service.id)) !== -1;
            return '<label class="checkbox" style="display:flex;margin-bottom:8px">' +
                '<input type="checkbox" value="' + service.id + '" data-service-checkbox' + (checked ? ' checked' : '') + '>' +
                '<span>' + UI.escape(service.name) + ' <span class="text-muted text-sm">\u00B7 ' + UI.escape(window.APP.money(Math.round(Number(service.starting_price) * 100))) + '</span></span>' +
                '</label>';
        }).join('');
    }

    window.Upload.bind({
        input: form.querySelector('[data-file-input]'),
        dropzone: form.querySelector('[data-dropzone]'),
        preview: form.querySelector('[data-preview]'),
        info: form.querySelector('[data-preview-name]')
    });

    function rowActions(row) {
        return '' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="packages.update" title="Edit"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="toggle" data-id="' + row.id + '" data-permission="packages.update" title="Toggle status"><i data-lucide="' + (row.status === 'active' ? 'toggle-right' : 'toggle-left') + '"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="packages.delete" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'packages-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/packages',
        perPage: 10,
        emptyText: 'No packages yet',
        emptyHint: 'Bundle services into your first package.',
        columns: [
            {
                label: 'Package',
                render: function (row) {
                    var names = row.service_names || [];
                    return '<div class="font-semibold">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm truncate" style="max-width:360px">' + UI.escape(names.join(', ') || 'No services') + '</div>';
                }
            },
            { label: 'Services', render: function (row) { return '<span class="badge badge-brand badge-plain">' + window.APP.number(row.service_count || 0) + '</span>'; } },
            { label: 'Base', render: function (row) { return UI.escape(window.APP.money(Math.round(Number(row.base_amount || 0) * 100))); } },
            { label: 'Discount', render: function (row) { return row.discount_type ? '<span class="badge badge-warning">' + UI.escape(window.APP.titleCase(row.discount_label || '')) + '</span>' : '\u2014'; } },
            { label: 'Total', render: function (row) { return '<span class="font-semibold text-brand">' + UI.escape(window.APP.money(Math.round(Number(row.total_amount || 0) * 100))) + '</span>'; } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' package' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openForm(record) {
        UI.clear(form);
        form.display_order.value = '0';
        form.querySelector('[data-preview]').classList.add('hidden');
        isEdit = !!record;
        UI.qs('[data-modal-title]', modal).textContent = isEdit ? 'Edit Package' : 'Add Package';
        renderServiceList([]);
        UI.openModal(modal);

        if (record) {
            API.get('/packages/' + record.id).then(function (data) {
                currentServices = data;
                UI.fill(form, data);
                renderServiceList((data.services || []).map(function (service) { return Number(service.id || service.service_id); }));
                if (data.cover_image) {
                    var preview = form.querySelector('[data-preview]');
                    preview.querySelector('img').src = imageUrl(data.cover_image);
                    preview.classList.remove('hidden');
                    form.querySelector('[data-preview-name]').textContent = 'Current cover image';
                }
            }).catch(function (error) { Alerts.error(error.message); });
        }
    }

    document.querySelectorAll('[data-open-package]').forEach(function (button) {
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
            API.patch('/packages/' + id + '/status', { status: next }).then(function () {
                Alerts.success('Package marked as ' + next + '.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        } else if (action === 'delete' && records[id]) {
            Alerts.confirm({ title: 'Delete package?', text: 'Packages referenced elsewhere cannot be deleted.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.del('/packages/' + id).then(function () {
                    Alerts.success('Package deleted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var data = new FormData();
        UI.qsa('input[name], select[name], textarea[name]', form).forEach(function (field) {
            if (field.type === 'file') {
                if (field.files && field.files[0]) {
                    data.append(field.name, field.files[0]);
                }
                return;
            }
            if (field.type === 'checkbox') {
                data.append(field.name, field.checked ? '1' : '0');
                return;
            }
            data.append(field.name, field.value);
        });
        var selected = UI.qsa('[data-service-checkbox]:checked', form).map(function (box) { return Number(box.value); });
        data.append('service_ids', JSON.stringify(selected));
        var id = data.get('id');
        UI.setLoading(submitBtn, true);
        var request = isEdit ? API.upload('POST', '/packages/' + id, data) : API.upload('POST', '/packages', data);
        request.then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success(isEdit ? 'Package updated.' : 'Package created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    API.get('/services', { per_page: 100 }).then(function (data) {
        services = (data && data.items) ? data.items : (Array.isArray(data) ? data : []);
        var keep = UI.qsa('[data-service-checkbox]:checked', form).map(function (box) { return Number(box.value); });
        renderServiceList(keep);
    }).catch(function (error) { Alerts.error(error.message); });
})();
