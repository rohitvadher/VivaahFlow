(function () {
    'use strict';

    var bookingId = Number(window.APP.recordId() || 0);
    var container = document.getElementById('booking-detail');
    var actionsEl = document.querySelector('[data-booking-actions]');
    var referenceEl = document.querySelector('[data-booking-reference]');
    var subtitleEl = document.querySelector('[data-booking-subtitle]');
    var editModal = document.getElementById('booking-edit-modal');
    var editForm = document.getElementById('booking-edit-form');
    var editSubmit = editForm.querySelector('[data-submit]');
    var eventModal = document.getElementById('event-modal');
    var eventForm = document.getElementById('event-form');
    var eventSubmit = eventForm.querySelector('[data-submit]');
    var assignModal = document.getElementById('assign-modal');
    var assignForm = document.getElementById('assign-form');
    var assignSubmit = assignForm.querySelector('[data-submit]');
    var scheduleModal = document.getElementById('schedule-modal');
    var scheduleForm = document.getElementById('schedule-form');
    var scheduleSubmit = scheduleForm.querySelector('[data-submit]');
    var paymentModal = document.getElementById('payment-modal');
    var paymentForm = document.getElementById('payment-form');
    var paymentSubmit = paymentForm.querySelector('[data-submit]');
    var staff = [];
    var current = null;
    var activeEventId = null;

    function applyPerms(scope) {
        if (window.APP.currentUser) {
            UI.applyPermissions(scope);
        } else {
            document.addEventListener('app:ready', function () { UI.applyPermissions(scope); }, { once: true });
        }
    }

    function toNumber(value) {
        var parsed = String(value === null || value === undefined ? '' : value).replace(/[^0-9.-]/g, '');
        return parsed === '' || parsed === '-' ? 0 : parseFloat(parsed);
    }

    function remainingValue(data) {
        return toNumber(data.total_amount) - toNumber(data.paid_amount);
    }

    function renderActions(data) {
        var html = '';
        if (data.status !== 'cancelled') {
            html += '<button type="button" class="btn btn-outline" data-action="edit" data-permission="bookings.update"><i data-lucide="pencil"></i> Edit</button>';
        }
        if (data.status !== 'cancelled' && data.status !== 'completed') {
            html += '<button type="button" class="btn btn-outline" data-action="schedule" data-permission="bookings.update"><i data-lucide="calendar-days"></i> Schedule</button>';
        }
        if (data.status !== 'cancelled' && data.payment_status !== 'paid') {
            html += '<button type="button" class="btn btn-primary" data-action="payment" data-permission="payments.create"><i data-lucide="wallet"></i> Record Payment</button>';
        }
        Workflow.bookingNext(data).forEach(function (status) {
            var danger = status === 'cancelled';
            html += '<button type="button" class="btn ' + (danger ? 'btn-outline' : 'btn-soft') + '" data-action="status" data-status="' + status + '" data-permission="bookings.status">' +
                '<i data-lucide="' + (danger ? 'circle-x' : 'circle-arrow-right') + '"></i> ' + window.APP.titleCase(status) + '</button>';
        });
        actionsEl.innerHTML = html;
        applyPerms(actionsEl);
    }

    function statCard(label, value, extra) {
        return '<div class="stat-card"><div class="stat-label">' + UI.escape(label) + '</div><div class="stat-value">' + value + '</div>' +
            (extra ? '<div class="text-muted text-sm mt-1">' + extra + '</div>' : '') + '</div>';
    }

    function eventCard(event) {
        var assignments = event.assignments || [];
        var assignmentHtml = assignments.length
            ? '<div class="timeline">' + assignments.map(function (assignment) {
                return '<div class="timeline-item"><div class="flex items-center justify-between gap-2">' +
                    '<div><span class="font-semibold text-sm">' + UI.escape(assignment.staff_name) + '</span>' +
                    (assignment.designation ? '<span class="text-muted text-sm"> \u00B7 ' + UI.escape(assignment.designation) + '</span>' : '') +
                    (assignment.role_note ? '<div class="text-muted text-xs">' + UI.escape(assignment.role_note) + '</div>' : '') + '</div>' +
                    '<div class="flex items-center gap-2">' + UI.statusBadge(assignment.status) +
                    (assignment.status === 'completed' ? '' : '<button type="button" class="btn-icon" data-complete-assignment="' + assignment.id + '" data-permission="assignments.manage" title="Mark complete"><i data-lucide="check"></i></button>') +
                    '<button type="button" class="btn-icon" data-remove-assignment="' + assignment.id + '" data-permission="assignments.manage" title="Remove" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>' +
                    '</div></div></div>';
            }).join('') + '</div>'
            : '<div class="text-muted text-sm">No staff assigned yet.</div>';

        var transitions = Workflow.eventNext(event).map(function (status) {
            var danger = status === 'cancelled';
            return '<button type="button" class="btn btn-sm ' + (danger ? 'btn-outline' : 'btn-soft') + '" data-event-status="' + status + '" data-event-id="' + event.id + '" data-permission="events.status">' + window.APP.titleCase(status) + '</button>';
        }).join('');

        return '<div class="card card-pad">' +
            '<div class="flex items-start justify-between gap-3">' +
            '<div class="min-w-0"><div class="font-semibold">' + UI.escape(event.title) + '</div>' +
            '<div class="text-muted text-sm">' + UI.escape(window.APP.formatDate(event.event_date)) + ' \u00B7 ' +
            UI.escape(Workflow.timeLabel(event.start_time)) + ' \u2013 ' + UI.escape(Workflow.timeLabel(event.end_time)) + '</div>' +
            (event.venue_address ? '<div class="text-muted text-xs mt-1">' + UI.escape(event.venue_address) + '</div>' : '') +
            '</div><div class="flex items-center gap-2">' + UI.statusBadge(event.status) +
            '<button type="button" class="btn-icon" data-edit-event="' + event.id + '" data-permission="events.update" title="Edit event"><i data-lucide="pencil"></i></button>' +
            '<button type="button" class="btn-icon" data-delete-event="' + event.id + '" data-permission="events.delete" title="Delete event" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>' +
            '</div></div>' +
            '<div class="mt-4">' + assignmentHtml + '</div>' +
            '<div class="flex items-center gap-2 mt-4 flex-wrap">' +
            '<button type="button" class="btn btn-sm btn-outline" data-assign-event="' + event.id + '" data-permission="assignments.manage"><i data-lucide="user-plus"></i> Assign Staff</button>' +
            transitions + '</div></div>';
    }

    function render(data, payments) {
        current = data;
        referenceEl.textContent = data.reference_no;
        subtitleEl.textContent = data.customer_name + ' \u00B7 ' + window.APP.formatDate(data.event_date) + ' \u00B7 ' + window.APP.titleCase(data.status);
        renderActions(data);

        var paymentSummary = '<div class="grid-stats">' +
            statCard('Total', data.total_formatted) +
            statCard('Paid', data.paid_formatted) +
            (data.payment_status === 'paid'
                ? statCard('Payment', '<span class="badge badge-success">Paid in full</span>', data.paid_formatted + ' received')
                : statCard('Remaining', data.remaining_formatted, 'Status: ' + Workflow.paymentStatusLabel(data.payment_status))) +
            '</div>';

        var servicesRows = (data.services || []).map(function (item) {
            return '<tr><td><div class="font-semibold">' + UI.escape(item.item_name) + '</div></td>' +
                '<td>' + window.APP.number(item.quantity) + '</td>' +
                '<td>' + Workflow.amount(item.unit_price) + '</td>' +
                '<td class="font-semibold">' + Workflow.amount(item.amount) + '</td></tr>';
        }).join('');

        var paymentRows = payments.length
            ? payments.map(function (payment) {
                return '<tr><td><span class="font-semibold">' + UI.escape(payment.reference_no) + '</span></td>' +
                    '<td>' + UI.escape(window.APP.formatDate(payment.payment_date)) + '</td>' +
                    '<td>' + UI.escape(payment.method) + '</td>' +
                    '<td class="font-semibold">' + UI.escape(payment.amount_formatted) + '</td>' +
                    '<td>' + UI.statusBadge(payment.status) + '</td></tr>';
            }).join('')
            : '';

        container.innerHTML =
            paymentSummary +
            '<div class="grid-3 mt-4">' +
            '<div class="grid-span-2 flex flex-col gap-4">' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Booked Services</h2></div>' +
            '<div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr></thead><tbody>' + servicesRows + '</tbody></table></div>' +
            '<div class="card-pad">' + summaryHtml(data) + '</div></div>' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Schedule</h2>' +
            '<button type="button" class="btn btn-sm btn-outline" data-action="event-new" data-permission="events.create"><i data-lucide="plus"></i> Add Event</button></div>' +
            '<div class="card-pad flex flex-col gap-4">' + ((data.events || []).length ? (data.events || []).map(eventCard).join('') : '<div class="text-muted text-sm">No events scheduled for this booking yet.</div>') + '</div></div>' +
            '</div>' +
            '<div class="flex flex-col gap-4">' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Customer</h2></div><div class="card-pad"><dl class="detail-list">' +
            Workflow.detailRow('Name', UI.escape(data.customer_name)) +
            Workflow.detailRow('Email', UI.escape(data.customer_email || '\u2014')) +
            Workflow.detailRow('Phone', UI.escape(data.customer_phone || '\u2014')) +
            Workflow.detailRow('Quotation', data.quotation_id ? '<a class="link" href="' + window.APP.route('manage.quotation', { id: data.quotation_id }) + '">' + UI.escape(data.quotation_no || ('#' + data.quotation_id)) + '</a>' : '\u2014') +
            Workflow.detailRow('Status', UI.statusBadge(data.status)) +
            '</dl></div></div>' +
            '<div class="card"><div class="card-head"><h2 class="card-title">Payments</h2></div>' +
            (payments.length
                ? '<div class="table-wrap"><table class="table"><thead><tr><th>Reference</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead><tbody>' + paymentRows + '</tbody></table></div>'
                : '<div class="card-pad text-muted text-sm">No payments recorded yet.</div>') +
            '</div>' +
            (data.notes ? '<div class="card"><div class="card-head"><h2 class="card-title">Notes</h2></div><div class="card-pad text-muted text-sm">' + UI.escape(data.notes) + '</div></div>' : '') +
            '</div></div>';

        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function summaryHtml(data) {
        return '<div class="totals-list">' + Workflow.summaryRows(data).map(function (row) {
            return '<div class="totals-row' + (row[0] === 'Total' ? ' is-total' : '') + '"><span>' + UI.escape(row[0]) + '</span><span>' + UI.escape(row[1]) + '</span></div>';
        }).join('') + '</div>';
    }

    function load() {
        Loader.skeleton(container, { rows: 2 });
        API.get('/bookings/' + bookingId).then(function (data) {
            API.get('/payments', { per_page: 100, search: data.reference_no }).then(function (result) {
                var payments = ((result && result.items) || []).filter(function (payment) {
                    return Number(payment.booking_id) === Number(data.id);
                });
                render(data, payments);
            }).catch(function () {
                render(data, []);
            });
        }).catch(function (error) {
            container.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">' + UI.escape(error.message) + '</div><a class="btn btn-outline mt-3" href="' + window.APP.route('manage.bookings') + '">Back to bookings</a></div></div>';
        });
    }

    document.addEventListener('click', function (event) {
        var action = event.target.closest('[data-action]');
        if (action && current) {
            var name = action.getAttribute('data-action');
            if (name === 'edit') {
                UI.clear(editForm);
                UI.fill(editForm, current);
                UI.openModal(editModal);
            } else if (name === 'schedule') {
                UI.clear(scheduleForm);
                scheduleForm.event_date.value = current.event_date || '';
                scheduleForm.venue_address.value = current.venue_address || '';
                UI.openModal(scheduleModal);
            } else if (name === 'payment') {
                UI.clear(paymentForm);
                paymentForm.amount.value = remainingValue(current).toFixed(2);
                paymentForm.payment_date.value = new Date().toISOString().slice(0, 10);
                UI.qs('[data-payment-summary]', paymentModal).textContent = 'Remaining balance ' + current.remaining_formatted + '.';
                UI.openModal(paymentModal);
            } else if (name === 'event-new') {
                UI.clear(eventForm);
                eventForm.id.value = '';
                eventForm.event_date.value = current.event_date || '';
                eventForm.venue_address.value = current.venue_address || '';
                UI.qs('[data-event-title]', eventModal).textContent = 'Add Event';
                UI.openModal(eventModal);
            } else if (name === 'status') {
                var status = action.getAttribute('data-status');
                Alerts.confirm({ title: 'Update booking status?', text: 'Mark ' + current.reference_no + ' as ' + window.APP.titleCase(status) + '.', confirmText: 'Confirm', danger: status === 'cancelled' }).then(function (confirmed) {
                    if (!confirmed) { return; }
                    API.patch('/bookings/' + current.id + '/status', { status: status }).then(function () {
                        Alerts.success('Booking updated.');
                        load();
                    }).catch(function (error) { Alerts.error(error.message); });
                });
            }
            return;
        }

        var editEvent = event.target.closest('[data-edit-event]');
        if (editEvent && current) {
            var target = (current.events || []).filter(function (item) { return Number(item.id) === Number(editEvent.getAttribute('data-edit-event')); })[0];
            if (target) {
                UI.clear(eventForm);
                UI.fill(eventForm, target);
                eventForm.id.value = target.id;
                UI.qs('[data-event-title]', eventModal).textContent = 'Edit Event';
                UI.openModal(eventModal);
            }
            return;
        }

        var deleteEvent = event.target.closest('[data-delete-event]');
        if (deleteEvent) {
            Alerts.confirm({ title: 'Delete event?', text: 'This removes the event and its assignments.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/events/' + deleteEvent.getAttribute('data-delete-event')).then(function () {
                    Alerts.success('Event deleted.');
                    load();
                }).catch(function (error) { Alerts.error(error.message); });
            });
            return;
        }

        var eventStatus = event.target.closest('[data-event-status]');
        if (eventStatus) {
            API.patch('/events/' + eventStatus.getAttribute('data-event-id') + '/status', { status: eventStatus.getAttribute('data-event-status') }).then(function () {
                Alerts.success('Event status updated.');
                load();
            }).catch(function (error) { Alerts.error(error.message); });
            return;
        }

        var assign = event.target.closest('[data-assign-event]');
        if (assign && current) {
            activeEventId = Number(assign.getAttribute('data-assign-event'));
            UI.clear(assignForm);
            var target = (current.events || []).filter(function (item) { return Number(item.id) === activeEventId; })[0];
            UI.qs('[data-assign-summary]', assignModal).textContent = target ? ('Assign a team member to "' + target.title + '" on ' + window.APP.formatDate(target.event_date) + '.') : '';
            UI.openModal(assignModal);
            return;
        }

        var completeAssignment = event.target.closest('[data-complete-assignment]');
        if (completeAssignment) {
            API.post('/events/assignments/' + completeAssignment.getAttribute('data-complete-assignment') + '/complete').then(function () {
                Alerts.success('Assignment completed.');
                load();
            }).catch(function (error) { Alerts.error(error.message); });
            return;
        }

        var removeAssignment = event.target.closest('[data-remove-assignment]');
        if (removeAssignment) {
            Alerts.confirm({ title: 'Remove assignment?', text: 'The staff member will no longer be assigned.', confirmText: 'Remove', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/events/assignments/' + removeAssignment.getAttribute('data-remove-assignment')).then(function () {
                    Alerts.success('Assignment removed.');
                    load();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    editForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(editForm);
        UI.setLoading(editSubmit, true);
        API.post('/bookings/' + current.id, payload).then(function () {
            UI.setLoading(editSubmit, false);
            UI.closeModal(editModal);
            Alerts.success('Booking updated.');
            load();
        }).catch(function (error) {
            UI.setLoading(editSubmit, false);
            if (error.errors) { UI.showErrors(editForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    scheduleForm.addEventListener('submit', function (event) {
        event.preventDefault();
        UI.setLoading(scheduleSubmit, true);
        API.post('/bookings/' + current.id + '/schedule', { event_date: scheduleForm.event_date.value, venue_address: scheduleForm.venue_address.value }).then(function () {
            UI.setLoading(scheduleSubmit, false);
            UI.closeModal(scheduleModal);
            Alerts.success('Booking scheduled.');
            load();
        }).catch(function (error) {
            UI.setLoading(scheduleSubmit, false);
            if (error.errors) { UI.showErrors(scheduleForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    paymentForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var amount = Number(paymentForm.amount.value);
        if (amount <= 0) {
            Alerts.error('Enter an amount greater than zero.');
            return;
        }
        if (amount > remainingValue(current)) {
            Alerts.error('Amount exceeds the remaining balance of ' + current.remaining_formatted + '.');
            return;
        }
        var payload = UI.serialize(paymentForm);
        payload.amount = amount;
        UI.setLoading(paymentSubmit, true);
        API.post('/payments', Object.assign({ booking_id: current.id }, payload)).then(function () {
            UI.setLoading(paymentSubmit, false);
            UI.closeModal(paymentModal);
            Alerts.success('Payment recorded.');
            load();
        }).catch(function (error) {
            UI.setLoading(paymentSubmit, false);
            if (error.errors) { UI.showErrors(paymentForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    eventForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(eventForm);
        var eventId = payload.id;
        delete payload.id;
        UI.setLoading(eventSubmit, true);
        var request = eventId
            ? API.post('/events/' + eventId, payload)
            : API.post('/events', Object.assign({ booking_id: current.id }, payload));
        request.then(function () {
            UI.setLoading(eventSubmit, false);
            UI.closeModal(eventModal);
            Alerts.success(eventId ? 'Event updated.' : 'Event added.');
            load();
        }).catch(function (error) {
            UI.setLoading(eventSubmit, false);
            if (error.errors) { UI.showErrors(eventForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    assignForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = UI.serialize(assignForm);
        UI.setLoading(assignSubmit, true);
        API.post('/events/' + activeEventId + '/assignments', payload).then(function () {
            UI.setLoading(assignSubmit, false);
            UI.closeModal(assignModal);
            Alerts.success('Staff assigned.');
            load();
        }).catch(function (error) {
            UI.setLoading(assignSubmit, false);
            if (error.errors) { UI.showErrors(assignForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    API.get('/staff/active').then(function (data) {
        staff = Array.isArray(data) ? data : [];
        UI.populate(assignForm.querySelector('[data-staff-select]'), staff, { placeholder: 'Select staff member', label: function (item) { return item.name + (item.designation ? ' \u00B7 ' + item.designation : ''); } });
    }).catch(function () { return; });

    if (bookingId) {
        load();
    } else {
        container.innerHTML = '<div class="card"><div class="table-empty"><div class="empty-title">No booking selected</div></div></div>';
    }
})();
