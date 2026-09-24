(function () {
    'use strict';

    var records = {};
    var tableEl = document.getElementById('reviews-table');
    var modal = document.getElementById('review-modal');
    var form = document.getElementById('review-form');
    var submit = form.querySelector('[data-submit]');
    var summaryBox = form.querySelector('[data-review-summary]');
    var activeId = null;

    function stars(rating) {
        var value = Number(rating) || 0;
        var html = '<span class="font-semibold">' + value + '/5</span> ';
        for (var i = 1; i <= 5; i += 1) {
            html += '<span style="color:' + (i <= value ? 'var(--brand-500)' : 'var(--ink-500)') + '">\u2605</span>';
        }
        return html;
    }

    function rowActions(row) {
        var html = '<button type="button" class="btn-icon" data-action="moderate" data-id="' + row.id + '" data-permission="reviews.moderate" title="Moderate"><i data-lucide="gavel"></i></button>';
        if (row.status === 'pending') {
            html += '<button type="button" class="btn-icon" data-action="approve" data-id="' + row.id + '" data-permission="reviews.moderate" title="Approve"><i data-lucide="check"></i></button>';
            html += '<button type="button" class="btn-icon" data-action="reject" data-id="' + row.id + '" data-permission="reviews.moderate" title="Reject" style="color:var(--danger)"><i data-lucide="x"></i></button>';
        }
        html += '<button type="button" class="btn-icon" data-action="delete" data-id="' + row.id + '" data-permission="reviews.delete" title="Delete review" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>';
        return html;
    }

    var table = window.Tables.create({
        table: tableEl,
        pagination: 'reviews-pagination',
        search: '[data-table-search]',
        status: '[data-table-status]',
        endpoint: '/reviews',
        perPage: 10,
        emptyText: 'No reviews yet',
        emptyHint: 'Customer reviews will appear here for moderation.',
        columns: [
            {
                label: 'Customer',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.customer_name) + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.booking_no || '') + '</div>';
                }
            },
            { label: 'Rating', render: function (row) { return stars(row.rating); } },
            {
                label: 'Review',
                render: function (row) {
                    return '<div class="font-semibold">' + UI.escape(row.title || '\u2014') + '</div>' +
                        '<div class="text-muted text-sm">' + UI.escape(row.comment || '').slice(0, 90) + '</div>';
                }
            },
            {
                label: 'Visible',
                render: function (row) { return Number(row.is_visible) ? '<span class="badge badge-info">Yes</span>' : '<span class="badge badge-muted">No</span>'; }
            },
            { label: 'Status', render: function (row) { return UI.statusBadge(row.status); } }
        ],
        rowActions: rowActions,
        onLoaded: function (items, data) {
            records = {};
            items.forEach(function (item) { records[item.id] = item; });
            var count = UI.qs('[data-count-label]');
            if (count && data && data.pagination) {
                count.textContent = window.APP.number(data.pagination.total) + ' review' + (data.pagination.total === 1 ? '' : 's');
            }
        }
    });

    function openModerate(record) {
        activeId = record.id;
        UI.clear(form);
        summaryBox.innerHTML = '<dl class="detail-list">' +
            Workflow.detailRow('Customer', UI.escape(record.customer_name)) +
            Workflow.detailRow('Booking', UI.escape(record.booking_no || '\u2014')) +
            Workflow.detailRow('Rating', stars(record.rating)) +
            Workflow.detailRow('Title', UI.escape(record.title || '\u2014')) +
            '</dl>' +
            '<div class="card card-pad mt-3 text-sm">' + UI.escape(record.comment || '') + '</div>';
        form.status.value = record.status;
        form.querySelector('[data-visible-toggle]').checked = Number(record.is_visible) === 1;
        form.reply.value = record.reply || '';
        UI.openModal(modal);
    }

    function moderate(id, payload) {
        API.patch('/reviews/' + id, payload).then(function () {
            Alerts.success('Review updated.');
            table.reload();
        }).catch(function (error) { Alerts.error(error.message); });
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

        if (action === 'moderate') {
            openModerate(record);
        } else if (action === 'approve') {
            moderate(record.id, { status: 'approved', is_visible: 1, reply: record.reply || '' });
        } else if (action === 'reject') {
            moderate(record.id, { status: 'rejected', is_visible: 0, reply: record.reply || '' });
        } else if (action === 'delete') {
            Alerts.confirm({ title: 'Delete review?', text: 'This review will be removed permanently.', confirmText: 'Delete', danger: true }).then(function (confirmed) {
                if (!confirmed) { return; }
                API.del('/reviews/' + record.id).then(function () {
                    Alerts.success('Review deleted.');
                    table.reload();
                }).catch(function (error) { Alerts.error(error.message); });
            });
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var payload = {
            status: form.status.value,
            is_visible: form.querySelector('[data-visible-toggle]').checked ? 1 : 0,
            reply: form.reply.value
        };
        UI.setLoading(submit, true);
        API.patch('/reviews/' + activeId, payload).then(function () {
            UI.setLoading(submit, false);
            UI.closeModal(modal);
            Alerts.success('Review updated.');
            table.reload();
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });
})();
