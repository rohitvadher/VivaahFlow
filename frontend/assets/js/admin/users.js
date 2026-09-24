(function () {
    'use strict';

    var records = {};
    var roles = [];
    var tableEl = document.getElementById('users-table');
    var modal = document.getElementById('user-modal');
    var form = document.getElementById('user-form');
    var submit = form.querySelector('[data-submit]');
    var roleSelect = form.querySelector('[data-role-select]');
    var roleFilter = document.querySelector('[data-role-filter]');
    var passwordLabel = form.querySelector('[data-password-label]');
    var currentUserId = null;

    function rowActions(row) {
        var html = '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="users.update" title="Edit user"><i data-lucide="pencil"></i></button>';
        if (Number(row.id) !== Number(currentUserId)) {
            var next = row.status === 'active' ? 'inactive' : 'active';
            html += '<button type="button" class="btn-icon" data-action="status" data-status="' + next + '" data-id="' + row.id + '" data-permission="users.update" title="' + (next === 'active' ? 'Activate' : 'Deactivate') + '"><i data-lucide="' + (next === 'active' ? 'user-check' : 'user-x') + '"></i></button>' +
                '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="users.delete" title="Delete user" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
        }
        return html;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'users-pagination',
        search: '[data-table-search]',
        filters: [{ el: '[data-role-filter]', key: 'role_id' }],
        endpoint: '/users',
        perPage: 10,
        emptyText: 'No users found',
        emptyHint: 'Add a user to give them panel access.',
        columns: [
            {
                label: 'User',
                render: function (row) {
                    return '<div class="flex items-center gap-3"><span class="avatar avatar-sm">' + UI.escape(window.APP.initials(row.name)) + '</span>' +
                        '<div><div class="font-semibold">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.email) + '</div></div></div>';
                }
            },
            { label: 'Role', render: function (row) { return '<span class="badge badge-brand badge-plain">' + UI.escape(row.role_name || '') + '</span>'; } },
            { label: 'Phone', render: function (row) { return UI.escape(row.phone || '\u2014'); } },
            { label: 'Last login', render: function (row) { return UI.escape(row.last_login_at ? window.APP.formatDateTime(row.last_login_at) : 'Never'); } },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' user' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openUser(record) {
        UI.clear(form);
        if (record) {
            UI.fill(form, record);
            form.id.value = record.id;
            form.role_id.value = record.role_id;
            form.password.value = '';
            passwordLabel.textContent = 'New password (optional)';
            UI.qs('[data-user-title]', modal).textContent = 'Edit User';
        } else {
            form.status.value = 'active';
            passwordLabel.textContent = 'Password';
            UI.qs('[data-user-title]', modal).textContent = 'Add User';
        }
        UI.openModal(modal);
    }

    UI.qs('[data-open-user]').addEventListener('click', function () { openUser(null); });

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
            openUser(record);
        } else if (action === 'status') {
            var status = trigger.getAttribute('data-status');
            API.patch('/users/' + record.id + '/status', { status: status }).then(function () {
                Alerts.success('User marked as ' + window.APP.titleCase(status) + '.');
                table.reload();
            }).catch(function (error) { Alerts.error(error.message); });
        } else if (action === 'delete') {
            Alerts.confirm({ title: 'Delete user?', text: record.name + ' will lose access permanently.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/users/' + record.id).then(function () {
                    Alerts.success('User deleted.');
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
        if (!payload.password) {
            delete payload.password;
        }
        UI.setLoading(submit, true);
        var request = id ? API.put('/users/' + id, payload) : API.post('/users', payload);
        request.then(function () {
            UI.setLoading(submit, false);
            UI.closeModal(modal);
            Alerts.success(id ? 'User updated.' : 'User created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });

    API.get('/auth/me').then(function (data) {
        currentUserId = data && data.user ? data.user.id : null;
    }).catch(function () { return; });

    API.get('/roles').then(function (data) {
        roles = Array.isArray(data) ? data : [];
        UI.populate(roleSelect, roles, { placeholder: 'Select role', label: function (item) { return item.name; } });
        UI.populate(roleFilter, roles, { placeholder: 'All roles', label: function (item) { return item.name; } });
    }).catch(function () {
        roleFilter.classList.add('hidden');
    });
})();
