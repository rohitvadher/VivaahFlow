(function () {
    'use strict';

    var records = {};
    var current = null;
    var staffList = [];
    var tableEl = document.getElementById('leads-table');
    var formModal = document.getElementById('lead-form-modal');
    var detailModal = document.getElementById('lead-detail-modal');
    var form = document.getElementById('lead-form');
    var submitBtn = form.querySelector('[data-submit]');
    var isEdit = false;

    var STATUSES = ['new', 'contacted', 'follow_up', 'converted', 'lost'];

    function staffOptions(selected) {
        return '<option value="">Unassigned</option>' + staffList.map(function (member) {
            return '<option value="' + member.id + '"' + (Number(selected) === Number(member.id) ? ' selected' : '') + '>' + UI.escape(member.name) + '</option>';
        }).join('');
    }

    function rowActions(row) {
        return '' +
            '<button type="button" class="btn-icon" data-action="view" data-id="' + row.id + '" title="View"><i data-lucide="eye"></i></button>' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="leads.update" title="Edit"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="leads.delete" title="Delete" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'leads-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/leads',
        perPage: 10,
        emptyText: 'No leads yet',
        emptyHint: 'Add a lead or convert an enquiry to get started.',
        columns: [
            {
                label: 'Lead',
                render: function (row) {
                    return '<div class="flex items-center gap-3"><span class="avatar">' + UI.escape(window.APP.initials(row.name)) + '</span>' +
                        '<div class="min-w-0"><div class="font-semibold truncate">' + UI.escape(row.name) + '</div>' +
                        '<div class="text-muted text-sm truncate">' + UI.escape(row.email || row.phone || '') + '</div></div></div>';
                }
            },
            { label: 'Source', render: function (row) { return UI.escape(window.APP.titleCase(row.source || '\u2014')); } },
            { label: 'Assigned', render: function (row) { return UI.escape(row.staff_name || 'Unassigned'); } },
            {
                label: 'Next Follow-up',
                render: function (row) {
                    var label = UI.escape(window.APP.formatDate(row.next_followup_at));
                    if (row.is_overdue) {
                        label += ' <span class="badge badge-danger">Overdue</span>';
                    }
                    return label;
                }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' lead' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openForm(record) {
        UI.clear(form);
        isEdit = !!record;
        UI.qs('[data-modal-title]', formModal).textContent = isEdit ? 'Edit Lead' : 'Add Lead';
        form.querySelector('[data-staff-select]').innerHTML = staffOptions(record ? record.assigned_to : null);
        if (isEdit) {
            UI.fill(form, record);
        }
        UI.openModal(formModal);
    }

    function renderDetail(lead) {
        current = lead;
        UI.qs('[data-lead-name]').textContent = lead.name;
        UI.qs('[data-lead-meta]').innerHTML = UI.statusBadge(lead.status) + ' <span class="text-muted text-sm">' + UI.escape(lead.email || lead.phone || '') + '</span>';

        var followups = lead.followups || [];
        var followupHtml = followups.length
            ? followups.map(function (row) {
                return '<div class="timeline-item"><div class="font-semibold text-sm">' + UI.escape(window.APP.formatDate(row.followup_date)) + '</div>' +
                    '<div class="text-sm">' + UI.escape(row.note || '') + '</div>' +
                    '<div class="text-muted text-xs mt-1">' + UI.statusBadge(row.status || 'pending') + ' \u00B7 ' + UI.escape(window.APP.timeAgo(row.created_at)) + '</div></div>';
            }).join('')
            : '<div class="text-muted text-sm">No follow-ups logged yet.</div>';

        UI.qs('[data-lead-body]').innerHTML =
            '<div class="detail-list mb-5">' +
            '<div class="detail-row"><dt>Source</dt><dd>' + UI.escape(window.APP.titleCase(lead.source || '\u2014')) + '</dd></div>' +
            '<div class="detail-row"><dt>Enquiry</dt><dd>' + UI.escape(lead.enquiry_no || '\u2014') + '</dd></div>' +
            '<div class="detail-row"><dt>Assigned to</dt><dd>' + UI.escape(lead.staff_name || 'Unassigned') + '</dd></div>' +
            '<div class="detail-row"><dt>Next follow-up</dt><dd>' + UI.escape(window.APP.formatDate(lead.next_followup_at)) + '</dd></div>' +
            '<div class="detail-row"><dt>Notes</dt><dd>' + UI.escape(lead.notes || '\u2014') + '</dd></div>' +
            '</div>' +
            '<div class="grid md:grid-2" style="gap:16px">' +
            '<div><label class="label">Update status</label><div class="flex gap-2">' +
            '<select class="form-control" data-lead-status>' + STATUSES.map(function (status) {
                return '<option value="' + status + '"' + (status === lead.status ? ' selected' : '') + '>' + window.APP.titleCase(status) + '</option>';
            }).join('') + '</select>' +
            '<button type="button" class="btn btn-soft" data-save-lead-status data-permission="leads.update">Save</button></div></div>' +
            '<div><label class="label">Assign to</label><div class="flex gap-2">' +
            '<select class="form-control" data-lead-assign>' + staffOptions(lead.assigned_to) + '</select>' +
            '<button type="button" class="btn btn-soft" data-save-lead-assign data-permission="leads.update">Assign</button></div></div>' +
            '</div>' +
            '<div class="card mt-5"><div class="card-head"><div class="card-title" style="font-size:14px">Log Follow-up</div></div>' +
            '<div class="card-pad"><div class="form-grid">' +
            '<div><label class="label">Follow-up date</label><input class="form-control" type="date" data-followup-date></div>' +
            '<div><label class="label">Status</label><select class="form-control" data-followup-status><option value="pending">Pending</option><option value="done">Done</option><option value="missed">Missed</option></select></div>' +
            '<div><label class="label">Next follow-up</label><input class="form-control" type="date" data-followup-next></div>' +
            '<div class="full"><label class="label">Note</label><textarea class="form-control" data-followup-note></textarea></div>' +
            '</div><div class="flex justify-end mt-3"><button type="button" class="btn btn-primary btn-sm" data-add-followup data-permission="followups.manage"><i data-lucide="plus"></i> Log Follow-up</button></div></div></div>' +
            '<div class="card mt-5"><div class="card-head"><div class="card-title" style="font-size:14px">Follow-up History</div></div><div class="card-pad"><div class="timeline">' + followupHtml + '</div></div></div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function openDetail(id) {
        UI.openModal(detailModal);
        Loader.skeleton(UI.qs('[data-lead-body]'), { rows: 2 });
        API.get('/leads/' + id).then(renderDetail).catch(function (error) { Alerts.error(error.message); });
    }

    document.querySelectorAll('[data-open-lead]').forEach(function (button) {
        button.addEventListener('click', function () { openForm(null); });
    });

    tableEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action]');
        if (!trigger) {
            return;
        }
        var id = Number(trigger.getAttribute('data-id'));
        var action = trigger.getAttribute('data-action');
        if (action === 'view') {
            openDetail(id);
        } else if (action === 'edit' && records[id]) {
            openForm(records[id]);
        } else if (action === 'delete' && records[id]) {
            Alerts.confirm({ title: 'Delete lead?', text: 'This lead will be permanently removed.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                API.del('/leads/' + id).then(function () {
                    Alerts.success('Lead deleted.');
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
        var request = isEdit ? API.put('/leads/' + id, payload) : API.post('/leads', payload);
        request.then(function () {
            UI.setLoading(submitBtn, false);
            UI.closeModal(formModal);
            Alerts.success(isEdit ? 'Lead updated.' : 'Lead created.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submitBtn, false);
            if (error.errors) {
                UI.showErrors(form, error.errors);
            }
            Alerts.error(error.message);
        });
    });

    detailModal.addEventListener('click', function (event) {
        if (!current) {
            return;
        }
        if (event.target.closest('[data-save-lead-status]')) {
            API.patch('/leads/' + current.id + '/status', { status: detailModal.querySelector('[data-lead-status]').value }).then(function () {
                Alerts.success('Lead status updated.');
                table.reload();
                openDetail(current.id);
            }).catch(function (error) { Alerts.error(error.message); });
        }
        if (event.target.closest('[data-save-lead-assign]')) {
            API.post('/leads/' + current.id + '/assign', { assigned_to: detailModal.querySelector('[data-lead-assign]').value }).then(function () {
                Alerts.success('Lead assigned.');
                table.reload();
                openDetail(current.id);
            }).catch(function (error) { Alerts.error(error.message); });
        }
        if (event.target.closest('[data-add-followup]')) {
            var payload = {
                followup_date: detailModal.querySelector('[data-followup-date]').value,
                status: detailModal.querySelector('[data-followup-status]').value,
                next_followup_at: detailModal.querySelector('[data-followup-next]').value,
                note: detailModal.querySelector('[data-followup-note]').value
            };
            if (!payload.followup_date) {
                Alerts.warning('Please choose a follow-up date.');
                return;
            }
            API.post('/leads/' + current.id + '/followups', payload).then(function () {
                Alerts.success('Follow-up logged.');
                table.reload();
                openDetail(current.id);
            }).catch(function (error) { Alerts.error(error.message); });
        }
    });

    API.get('/staff/active').then(function (data) {
        staffList = (data && data.items) ? data.items : (Array.isArray(data) ? data : []);
        form.querySelector('[data-staff-select]').innerHTML = staffOptions(null);
    }).catch(function () {
        return;
    });
})();
