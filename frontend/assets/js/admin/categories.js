(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('categories-table');
    var modal = document.getElementById('category-modal');
    var form = document.getElementById('category-form');
    var submitBtn = form.querySelector('[data-submit]');
    var isEdit = false;

    function openForm(record) {
        UI.clear(form);
        form.display_order.value = '0';
        isEdit = !!record;
        UI.qs('[data-modal-title]', modal).textContent = isEdit ? 'Edit Category' : 'Add Category';
        if (isEdit) {
            UI.fill(form, record);
        }
        UI.openModal(modal);
    }

    function rowActions(row) {
        return '' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="categories.update" title="Edit"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="categories.delete" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        search: '[data-table-search]',
        endpoint: '/categories',
        emptyText: 'No categories yet',
        emptyHint: 'Create a category to organise your services.',
        columns: [
            {
                label: 'Category',
                render: function (row) {
                    return '<div class="flex items-center gap-3"><span class="feature-icon" style="width:36px;height:36px;margin:0;border-radius:10px"><i data-lucide="folder"></i></span>' +
                        '<span class="font-semibold">' + UI.escape(row.name) + '</span></div>';
                }
            },
            {
                label: 'Services',
                render: function (row) {
                    return '<span class="badge badge-brand badge-plain">' + window.APP.number(row.service_count || 0) + ' services</span>';
                }
            }
        ],
        rowActions: rowActions,
        onLoaded: function (items) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
        }
    });

    document.querySelectorAll('[data-open-category]').forEach(function (button) {
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
        } else if (action === 'delete' && records[id]) {
            Alerts.confirm({ title: 'Delete category?', text: 'Categories with services cannot be deleted.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.del('/categories/' + id).then(function () {
                    Alerts.success('Category deleted.');
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
        var request = isEdit ? API.put('/categories/' + id, payload) : API.post('/categories', payload);
        request.then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(modal);
            Alerts.success(isEdit ? 'Category updated.' : 'Category created.');
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
