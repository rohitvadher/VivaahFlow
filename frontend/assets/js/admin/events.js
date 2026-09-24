(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('events-table');
    var schedulePanel = document.getElementById('schedule-panel');
    var scheduleDate = document.querySelector('[data-schedule-date]');
    var statusFilter = document.querySelector('[data-table-status]');
    var modal = document.getElementById('event-modal');
    var form = document.getElementById('event-form');
    var submit = form.querySelector('[data-submit]');

    function rowActions(row) {
        var html = '<button type="button" class="btn-icon" data-action="booking" data-id="' + row.id + '" title="Open booking"><i data-lucide="external-link"></i></button>' +
            '<button type="button" class="btn-icon" data-action="edit" data-id="' + row.id + '" data-permission="events.update" title="Edit event"><i data-lucide="pencil"></i></button>';
        Workflow.eventNext(row).forEach(function (status) {
            var danger = status === 'cancelled';
            html += '<button type="button" class="btn-icon" data-action="status" data-status="' + status + '" data-id="' + row.id + '" data-permission="events.status" title="Mark ' + window.APP.titleCase(status) + '"' + (danger ? ' style="color:var(--danger)"' : '') + '><i data-lucide="' + (danger ? 'circle-x' : 'circle-check') + '"></i></button>';
        });
        html += '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="events.delete" title="Delete event" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
        return html;
    }

    function teamCell(row) {
        var assignments = row.assignments || [];
        if (!assignments.length) {
            return '<span class="badge badge-warning">Unassigned</span>';
        }
        return assignments.map(function (assignment) {
            return '<span class="badge badge-plain">' + UI.escape(assignment.staff_name) + '</span>';
        }).join(' ');
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'events-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        filters: [{ el: '[data-filter-date]', key: 'date' }],
        endpoint: '/events',
        perPage: 10,
        emptyText: 'No events found',
        emptyHint: 'Events appear here once a booking is converted.',
        columns: [
            {
                label: 'Event',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.title) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(Workflow.timeLabel(row.start_time)) + ' \u2013 ' + UI.escape(Workflow.timeLabel(row.end_time)) + '</div>';
                }
            },
            {
                label: 'Booking',
                render: function (row) {
                    return '<a class="link-brand" href="' + window.APP.route('manage.booking', { id: row.booking_id }) + '">' + UI.escape(row.booking_no) + '</a>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.customer_name) + '</div>';
                }
            },
            {
                label: 'Date',
                render: function (row) { return UI.escape(window.APP.formatDate(row.event_date)); }
            },
            { label: 'Team', render: teamCell },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' event' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function loadSchedule() {
        var date = scheduleDate.value || new Date().toISOString().slice(0, 10);
        var status = statusFilter.value;
        Loader.skeleton(schedulePanel, { rows: 2 });
        API.get('/events/schedule', { date: date, status: status || undefined }).then(function (data) {
            var events = (data && data.events) || [];
            if (!events.length) {
                schedulePanel.innerHTML = '<div class="text-muted text-sm">No events on ' + UI.escape(window.APP.formatDate(date)) + '.</div>';
                return;
            }
            schedulePanel.innerHTML = '<div class="timeline">' + events.map(function (event) {
                return '<div class="timeline-item">' +
                    '<div class="flex items-center justify-between gap-2">' +
                    '<span class="font-semibold text-sm">' + UI.escape(Workflow.timeLabel(event.start_time)) + '</span>' +
                    UI.statusBadge(event.status) + '</div>' +
                    '<div class="font-semibold">' + UI.escape(event.title) + '</div>' +
                    '<div class="text-muted text-sm">' + UI.escape(event.booking_no || '') + ' \u00B7 ' + UI.escape(event.customer_name || '') + '</div>' +
                    (event.venue_address ? '<div class="text-muted text-xs mt-1">' + UI.escape(event.venue_address) + '</div>' : '') +
                    '</div>';
            }).join('') + '</div>';
        }).catch(function (error) {
            schedulePanel.innerHTML = '<div class="text-muted text-sm">' + UI.escape(error.message) + '</div>';
        });
    }

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

        if (action === 'booking') {
            window.location.href = window.APP.route('manage.booking', { id: record.booking_id });
        } else if (action === 'edit') {
            UI.clear(form);
            UI.fill(form, record);
            form.id.value = record.id;
            UI.openModal(modal);
        } else if (action === 'delete') {
            Alerts.confirm({ title: 'Delete event?', text: record.title + ' and its assignments will be removed.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/events/' + record.id).then(function () {
                    Alerts.success('Event deleted.');
                    table.reload();
                    loadSchedule();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        } else if (action === 'status') {
            var status = trigger.getAttribute('data-status');
            API.patch('/events/' + record.id + '/status', { status: status }).then(function () {
                Alerts.success('Event marked as ' + window.APP.titleCase(status) + '.');
                table.reload();
                loadSchedule();
            }).catch(function (error) { Alerts.error(error.message); });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(form);
        var id = payload.id;
        delete payload.id;
        UI.setLoading(submit, true);
        API.post('/events/' + id, payload).then(function () {
            UI.setLoading(submit, false);
            UI.closeModal(modal);
            Alerts.success('Event updated.');
            table.reload();
            loadSchedule();
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });

    scheduleDate.addEventListener('change', loadSchedule);
    statusFilter.addEventListener('change', loadSchedule);

    scheduleDate.value = new Date().toISOString().slice(0, 10);
    loadSchedule();
})();
