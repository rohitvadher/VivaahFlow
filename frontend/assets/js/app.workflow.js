(function () {
    'use strict';

    var QUOTATION_TRANSITIONS = {
        draft: ['sent', 'accepted', 'rejected'],
        sent: ['accepted', 'rejected', 'expired'],
        accepted: [],
        rejected: [],
        expired: []
    };

    var BOOKING_TRANSITIONS = {
        pending: ['confirmed', 'cancelled'],
        confirmed: ['scheduled', 'in_progress', 'cancelled'],
        scheduled: ['in_progress', 'completed', 'cancelled'],
        in_progress: ['completed', 'cancelled'],
        completed: [],
        cancelled: []
    };

    var EVENT_TRANSITIONS = {
        scheduled: ['in_progress', 'completed', 'cancelled'],
        in_progress: ['completed', 'cancelled'],
        completed: [],
        cancelled: []
    };

    function next(current, map) {
        if (!current || !map[current]) {
            return [];
        }
        return map[current].slice();
    }

    function has(value) {
        return Number(value) > 0;
    }

    function quotationCanAccept(quotation) {
        if (!quotation) {
            return false;
        }
        if (quotation.status !== 'draft' && quotation.status !== 'sent') {
            return false;
        }
        if (quotation.is_expired) {
            return false;
        }
        if (quotation.valid_until) {
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var until = new Date(String(quotation.valid_until).replace(' ', 'T'));
            if (!isNaN(until.getTime()) && until < today) {
                return false;
            }
        }
        return true;
    }

    function quotationCanConvert(quotation) {
        return !!quotation && quotation.status === 'accepted' && !quotation.booking_id;
    }

    function paymentStatusLabel(status) {
        var labels = {
            unpaid: 'Unpaid',
            partial: 'Partially paid',
            paid: 'Paid'
        };
        return labels[status] || window.APP.titleCase(status || 'unpaid');
    }

    function money(cents) {
        return window.APP.money(Number(cents) || 0);
    }

    function amount(rupees) {
        return window.APP.money(Math.round((Number(rupees) || 0) * 100));
    }

    function summaryRows(options) {
        var rows = [];
        rows.push(['Subtotal', options.subtotal_formatted || amount(options.subtotal), false]);
        if (Number(options.discount_amount) > 0) {
            rows.push(['Discount' + (options.discount_label ? ' (' + options.discount_label + ')' : ''), '-' + (options.discount_formatted || amount(options.discount_amount)), true]);
        }
        rows.push(['Total', options.total_formatted || amount(options.total_amount), false]);
        return rows;
    }

    function detailRow(label, value) {
        return '<div class="detail-row"><dt>' + window.UI.escape(label) + '</dt><dd>' + value + '</dd></div>';
    }

    function timeLabel(value) {
        if (!value) {
            return '\u2014';
        }
        var parts = String(value).split(':');
        var hours = Number(parts[0]);
        var minutes = parts.length > 1 ? parts[1] : '00';
        if (isNaN(hours)) {
            return String(value);
        }
        var suffix = hours >= 12 ? 'PM' : 'AM';
        var display = hours % 12;
        if (display === 0) {
            display = 12;
        }
        return display + ':' + minutes + ' ' + suffix;
    }

    function dateLabel(value) {
        return window.APP.formatDate(value);
    }

    function fillSelect(select, values, current, labels) {
        if (!select) {
            return;
        }
        var html = values.map(function (value) {
            return '<option value="' + window.UI.escape(value) + '"' + (value === current ? ' selected' : '') + '>' + window.UI.escape((labels && labels[value]) || window.APP.titleCase(value)) + '</option>';
        }).join('');
        select.innerHTML = html;
    }

    function actionButtons(transitions, permission, labels) {
        return transitions.map(function (status) {
            var danger = status === 'cancelled' || status === 'rejected' || status === 'expired';
            return '<button type="button" class="btn btn-sm ' + (danger ? 'btn-outline' : 'btn-primary') + '" data-status-action="' + status + '" data-permission="' + permission + '">' +
                '<i data-lucide="' + (status === 'cancelled' || status === 'rejected' ? 'circle-x' : 'circle-check-big') + '"></i> ' +
                window.UI.escape((labels && labels[status]) || window.APP.titleCase(status)) + '</button>';
        }).join('');
    }

    window.Workflow = {
        quotationTransitions: QUOTATION_TRANSITIONS,
        bookingTransitions: BOOKING_TRANSITIONS,
        eventTransitions: EVENT_TRANSITIONS,
        quotationNext: function (quotation) { return next(quotation && quotation.status, QUOTATION_TRANSITIONS); },
        bookingNext: function (booking) { return next(booking && booking.status, BOOKING_TRANSITIONS); },
        eventNext: function (event) { return next(event && event.status, EVENT_TRANSITIONS); },
        quotationCanAccept: quotationCanAccept,
        quotationCanConvert: quotationCanConvert,
        paymentStatusLabel: paymentStatusLabel,
        summaryRows: summaryRows,
        detailRow: detailRow,
        timeLabel: timeLabel,
        dateLabel: dateLabel,
        fillSelect: fillSelect,
        actionButtons: actionButtons,
        money: money,
        amount: amount
    };
})();
