(function () {
    'use strict';

    function icon(name, className) {
        return '<i data-lucide="' + name + '" class="' + (className || '') + '"></i>';
    }

    function refreshIcons() {
        if (window.Icons && typeof window.Icons.refresh === 'function') {
            window.Icons.refresh(document);
            return;
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try {
                window.lucide.createIcons();
            } catch (error) { /* ignore */ }
        }
    }

    function stack() {
        var el = document.getElementById('toast-stack');
        if (!el) {
            el = document.createElement('div');
            el.id = 'toast-stack';
            el.className = 'toast-stack';
            document.body.appendChild(el);
        }
        return el;
    }

    var ICONS = {
        success: 'circle-check-big',
        error: 'octagon-alert',
        warning: 'triangle-alert',
        info: 'info'
    };

    var MAX_TOASTS = 4;

    function toast(message, type, title) {
        var kind = type || 'info';
        var host = stack();
        while (host.children.length >= MAX_TOASTS && host.firstChild) {
            host.removeChild(host.firstChild);
        }
        var node = document.createElement('div');
        node.className = 'toast toast-' + kind;
        node.setAttribute('role', 'status');
        node.innerHTML =
            icon(ICONS[kind] || 'info', 'toast-icon') +
            '<div class="flex-1">' +
            '<div class="toast-title">' + (title || window.APP.titleCase(kind)) + '</div>' +
            '<div class="toast-text">' + String(message || '') + '</div>' +
            '</div>' +
            '<button type="button" class="btn-icon" data-toast-close aria-label="Dismiss">' +
            icon('x', '') +
            '</button>';
        host.appendChild(node);
        refreshIcons();

        var timer = null;
        var removed = false;
        var remove = function () {
            if (removed) {
                return;
            }
            removed = true;
            if (timer !== null) {
                window.clearTimeout(timer);
                timer = null;
            }
            node.classList.add('is-leaving');
            window.setTimeout(function () {
                if (node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            }, 200);
        };
        node.querySelector('[data-toast-close]').addEventListener('click', remove);
        timer = window.setTimeout(remove, kind === 'error' ? 6500 : 4200);
        return node;
    }

    function confirmDialog(options) {
        options = options || {};
        return new Promise(function (resolve) {
            var modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML =
                '<div class="modal-dialog modal-sm">' +
                '<div class="modal-body" style="padding-top:28px">' +
                '<div class="flex items-center gap-3 mb-4">' +
                '<span class="stat-icon" style="margin:0;background:' + (options.danger ? 'var(--danger-bg)' : 'var(--brand-50)') + ';color:' + (options.danger ? 'var(--danger)' : 'var(--brand-600)') + '">' +
                icon(options.danger ? 'triangle-alert' : 'circle-help', '') +
                '</span>' +
                '<div><h3 class="card-title">' + (options.title || 'Are you sure?') + '</h3></div>' +
                '</div>' +
                '<p class="text-muted text-sm">' + (options.text || 'This action cannot be undone.') + '</p>' +
                '<div class="flex justify-end gap-2 mt-6">' +
                '<button type="button" class="btn btn-ghost" data-cancel>' + (options.cancelText || 'Cancel') + '</button>' +
                '<button type="button" class="btn ' + (options.danger ? 'btn-danger' : 'btn-primary') + '" data-confirm>' + (options.confirmText || 'Confirm') + '</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            document.body.appendChild(modal);
            refreshIcons();

            var settled = false;
            var close = function (result) {
                if (settled) {
                    return;
                }
                settled = true;
                document.removeEventListener('keydown', onKey);
                if (modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
                resolve(result);
            };
            var onKey = function (event) {
                if (event.key === 'Escape') {
                    close(false);
                }
            };
            modal.querySelector('[data-cancel]').addEventListener('click', function () { close(false); });
            modal.querySelector('[data-confirm]').addEventListener('click', function () { close(true); });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    close(false);
                }
            });
            document.addEventListener('keydown', onKey);
            var confirmBtn = modal.querySelector('[data-confirm]');
            if (confirmBtn) {
                confirmBtn.focus();
            }
        });
    }

    window.Alerts = {
        toast: toast,
        success: function (message, title) { return toast(message, 'success', title); },
        error: function (message, title) { return toast(message, 'error', title); },
        warning: function (message, title) { return toast(message, 'warning', title); },
        info: function (message, title) { return toast(message, 'info', title); },
        confirm: confirmDialog,
        refreshIcons: refreshIcons
    };
})();
