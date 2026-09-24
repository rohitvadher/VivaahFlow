(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('staff-table');
    var modal = document.getElementById('staff-modal');
    var form = document.getElementById('staff-form');
    var submit = form.querySelector('[data-submit]');
    var loginFields = form.querySelector('[data-login-fields]');
    var loginToggle = form.querySelector('[data-login-toggle]');
    var passwordField = form.querySelector('[data-password-field]');

    function toggleLogin() {
        passwordField.classList.toggle('hidden', !loginToggle.checked);
    }

    function rowActions(row) {
        var html = '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="staff.update" title="Edit staff"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="status" data-status="' + (row.status === 'active' ? 'inactive' : 'active') + '" data-id="' + row.id + '" data-permission="staff.update" title="' + (row.status === 'active' ? 'Deactivate' : 'Activate') + '"><i data-lucide="' + (row.status === 'active' ? 'user-x' : 'user-check') + '"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="staff.delete" title="Delete staff" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
        return html;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'staff-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/staff',
        perPage: 10,
        emptyText: 'No staff members yet',
        emptyHint: 'Add your team so they can be assigned to events.',
        columns: [
            {
                label: 'Name',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.email || '\u2014') + '</div>';
                }
            },
            {
                label: 'Role',
                render: function (row) {
                    return UI.escape(row.designation || '\u2014') +
                        (row.specialty ? '<div class="text-muted text-sm">' + UI.escape(row.specialty) + '</div>' : '');
                }
            },
            { label: 'Phone', render: function (row) { return UI.escape(row.phone || '\u2014'); } },
            {
                label: 'Login',
                render: function (row) { return row.user_id ? '<span class="badge badge-info">Linked</span>' : '<span class="badge badge-muted">None</span>'; }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' staff member' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openStaff(record) {
        UI.clear(form);
        loginFields.classList.remove('hidden');
        if (record) {
            UI.fill(form, record);
            form.id.value = record.id;
            loginFields.classList.add('hidden');
            UI.qs('[data-staff-title]', modal).textContent = 'Edit Staff';
        } else {
            form.status.value = 'active';
            UI.qs('[data-staff-title]', modal).textContent = 'Add Staff';
        }
        loginToggle.checked = false;
        toggleLogin();
        UI.openModal(modal);
    }

    UI.qs('[data-open-staff]').addEventListener('click', function () { openStaff(null); });
    loginToggle.addEventListener('change', toggleLogin);

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var record = records[Number(trigger.getAttribute('data-id'))];
        if (!record) {
            return;
        }
        var action = trigger.getAttribute('data-action');

        if (action === 'edit') {
            openStaff(record);
        } else if (action === 'status') {
            var status = trigger.getAttribute('data-status');
            API.patch('/staff/' + record.id + '/status', { status: status }).then(function () {
                Alerts.success('Staff marked as ' + window.APP.titleCase(status) + '.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        } else if (action === 'delete') {
            Alerts.confirm({ title: 'Delete staff member?', text: record.name + ' will be removed. Existing assignments stay in history.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/staff/' + record.id).then(function () {
                    Alerts.success('Staff member deleted.');
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
        if (!id && !loginToggle.checked) {
            delete payload.password;
        }
        if (id) {
            delete payload.create_login;
            delete payload.password;
        }
        UI.setLoading(submit, true);
        var request = id ? API.put('/staff/' + id, payload) : API.post('/staff', payload);
        request.then(function () {
            UI.setLoading(submit, false);
            UI.closeModal(modal);
            Alerts.success(id ? 'Staff member updated.' : 'Staff member created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });
})();
