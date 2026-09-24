(function () {
    'use strict';

    var listEl = document.getElementById('notification-list');
    var filterEl = document.querySelector('[data-notification-filter]');
    var unreadEl = document.querySelector('[data-unread-label]');
    var all = [];

    function iconFor(type) {
        var map = {
            success: 'circle-check-big',
            warning: 'triangle-alert',
            danger: 'circle-alert',
            info: 'info'
        };
        return map[type] || 'bell';
    }

    function badgeClass(type) {
        if (type === 'success') { return 'badge-success'; }
        if (type === 'warning') { return 'badge-warning'; }
        if (type === 'danger') { return 'badge-danger'; }
        return 'badge-info';
    }

    function render() {
        var showUnread = filterEl.value === 'unread';
        var rows = showUnread ? all.filter(function (item) { return !Number(item.is_read); }) : all;
        if (!rows.length) {
            listEl.innerHTML = '<div class="table-empty"><div class="empty-title">' + (showUnread ? 'No unread notifications' : 'No notifications yet') + '</div></div>';
            return;
        }
        listEl.innerHTML = '<div class="timeline">' + rows.map(function (item) {
            var unread = !Number(item.is_read);
            return '<div class="timeline-item' + (unread ? ' is-unread' : '') + '">' +
                '<div class="flex items-start justify-between gap-3">' +
                '<div class="flex items-start gap-3 min-w-0">' +
                '<span class="badge ' + badgeClass(item.notification_type) + ' badge-plain"><i data-lucide="' + iconFor(item.notification_type) + '"></i></span>' +
                '<div class="min-w-0"><div class="font-semibold text-sm">' + UI.escape(item.title) + '</div>' +
                '<div class="text-muted text-sm">' + UI.escape(item.message || '') + '</div>' +
                '<div class="text-muted text-xs mt-1">' + UI.escape(window.APP.formatDateTime(item.created_at)) + '</div></div>' +
                '</div>' +
                '<div class="flex items-center gap-2">' +
                (item.link ? '<a class="btn-icon" href="' + UI.escape(window.APP.linkUrl(item.link)) + '" title="Open"><i data-lucide="arrow-up-right"></i></a>' : '') +
                (unread ? '<button type="button" class="btn-icon" data-read="' + item.id + '" data-permission="notifications.update" title="Mark read"><i data-lucide="check"></i></button>' : '') +
                '</div></div></div>';
        }).join('') + '</div>';
        if (window.Icons && typeof window.Icons.refresh === 'function') { window.Icons.refresh(document); } else if (window.lucide) { try { window.lucide.createIcons(); } catch (error) { /* ignore */ } }
    }

    function load() {
        Loader.skeleton(listEl, { rows: 2 });
        API.get('/notifications').then(function (data) {
            all = (data && data.items) || [];
            unreadEl.textContent = window.APP.number((data && data.unread) || 0) + ' unread';
            render();
        }).catch(function (error) {
            listEl.innerHTML = '<div class="table-empty"><div class="empty-title">' + UI.escape(error.message) + '</div></div>';
        });
    }

    filterEl.addEventListener('change', render);

    listEl.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-read]');
        if (!trigger) {
            return;
        }
        API.post('/notifications/' + trigger.getAttribute('data-read') + '/read').then(function () {
            load();
        }).catch(function (error) { Alerts.error(error.message); });
    });

    document.querySelector('[data-mark-all]').addEventListener('click', function () {
        API.post('/notifications/read-all').then(function () {
            Alerts.success('All notifications marked as read.');
            load();
        }).catch(function (error) { Alerts.error(error.message); });
    });

    load();
})();
