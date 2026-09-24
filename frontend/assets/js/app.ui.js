(function () {
    'use strict';

    function qs(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function qsa(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function escape(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function icon(name, className) {
        return '<i data-lucide="' + name + '" class="' + (className || '') + '"></i>';
    }

    var STATUS_STYLES = {
        active: 'badge-success',
        inactive: 'badge-muted',
        pending: 'badge-warning',
        confirmed: 'badge-info',
        scheduled: 'badge-info',
        in_progress: 'badge-brand',
        completed: 'badge-success',
        cancelled: 'badge-danger',
        new: 'badge-info',
        contacted: 'badge-warning',
        follow_up: 'badge-brand',
        quotation_pending: 'badge-warning',
        quotation_sent: 'badge-info',
        converted: 'badge-success',
        closed: 'badge-muted',
        lost: 'badge-danger',
        draft: 'badge-muted',
        sent: 'badge-info',
        accepted: 'badge-success',
        rejected: 'badge-danger',
        expired: 'badge-warning',
        recorded: 'badge-success',
        reversed: 'badge-danger',
        issued: 'badge-info',
        partial: 'badge-warning',
        paid: 'badge-success',
        overdue: 'badge-danger',
        approved: 'badge-success',
        done: 'badge-success',
        missed: 'badge-danger',
        unpaid: 'badge-warning'
    };

    function statusBadge(status) {
        var value = String(status || '').toLowerCase();
        var style = STATUS_STYLES[value] || 'badge-muted';
        return '<span class="badge ' + style + '">' + escape(window.APP.titleCase(value || '\u2014')) + '</span>';
    }

    function setLoading(button, loading) {
        if (window.Loader && typeof window.Loader.button === 'function') {
            window.Loader.button(button, loading);
            return;
        }
        if (!button) {
            return;
        }
        if (loading) {
            button.classList.add('is-loading');
            button.disabled = true;
        } else {
            button.classList.remove('is-loading');
            button.disabled = false;
        }
    }

    function refreshIcons(scope) {
        if (window.Icons && typeof window.Icons.refresh === 'function') {
            window.Icons.refresh(scope || document);
            return;
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try {
                window.lucide.createIcons();
            } catch (error) { /* ignore */ }
        }
    }

    function openModal(id) {
        var modal = typeof id === 'string' ? document.getElementById(id) : id;
        if (modal) {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            refreshIcons(modal);
        }
    }

    function closeModal(id) {
        var modal = typeof id === 'string' ? document.getElementById(id) : id;
        if (modal) {
            modal.hidden = true;
            if (!qs('.modal:not([hidden])')) {
                document.body.style.overflow = '';
            }
        }
    }

    function populate(select, items, options) {
        if (!select) {
            return;
        }
        options = options || {};
        var valueKey = options.value || 'id';
        var labelKey = options.label || 'name';
        var current = select.value;
        var html = options.placeholder ? '<option value="">' + escape(options.placeholder) + '</option>' : '';
        (items || []).forEach(function (item) {
            var value = item[valueKey];
            var label = typeof labelKey === 'function' ? labelKey(item) : item[labelKey];
            html += '<option value="' + escape(value) + '">' + escape(label) + '</option>';
        });
        select.innerHTML = html;
        if (current) {
            select.value = current;
        }
    }

    function serialize(form) {
        var data = {};
        qsa('input, select, textarea', form).forEach(function (field) {
            if (!field.name || field.disabled) {
                return;
            }
            if (field.type === 'checkbox') {
                data[field.name] = field.checked ? (field.value || 1) : 0;
                return;
            }
            if (field.type === 'radio') {
                if (field.checked) {
                    data[field.name] = field.value;
                }
                return;
            }
            data[field.name] = field.value;
        });
        return data;
    }

    function fill(form, record) {
        var formEl = typeof form === 'string' ? document.getElementById(form) : form;
        if (!formEl) {
            return;
        }
        qsa('[name]', formEl).forEach(function (field) {
            if (!(field.name in record)) {
                return;
            }
            var value = record[field.name];
            if (field.type === 'checkbox') {
                field.checked = !!Number(value);
            } else {
                field.value = value === null || value === undefined ? '' : value;
            }
        });
    }

    function clear(form) {        var formEl = typeof form === 'string' ? document.getElementById(form) : form;
        if (!formEl) {
            return;
        }
        formEl.reset();
        qsa('.is-invalid', formEl).forEach(function (el) { el.classList.remove('is-invalid'); });
        qsa('.form-error', formEl).forEach(function (el) { el.textContent = ''; });
        qsa('input[type="hidden"]', formEl).forEach(function (el) { el.value = ''; });
    }

    function showErrors(form, errors) {
        if (!errors) {
            return;
        }
        Object.keys(errors).forEach(function (field) {
            var message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            var input = form ? qs('[name="' + field + '"]', form) : null;
            if (input) {
                input.classList.add('is-invalid');
                var holder = qs('[data-error-for="' + field + '"]', form);
                if (holder) {
                    holder.textContent = message;
                }
            }
        });
    }

    function renderPagination(container, pagination, onPage) {
        if (!container || !pagination) {
            return;
        }
        var page = Number(pagination.page) || 1;
        var last = Number(pagination.last_page) || 1;
        var from = pagination.from || 0;
        var to = pagination.to || 0;
        var total = pagination.total || 0;

        var buttons = '';
        buttons += '<button type="button" class="page-btn" data-page="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>' + icon('chevron-left') + '</button>';
        var start = Math.max(1, page - 2);
        var end = Math.min(last, start + 4);
        start = Math.max(1, end - 4);
        for (var index = start; index <= end; index++) {
            buttons += '<button type="button" class="page-btn ' + (index === page ? 'is-active' : '') + '" data-page="' + index + '">' + index + '</button>';
        }
        buttons += '<button type="button" class="page-btn" data-page="' + (page + 1) + '"' + (page >= last ? ' disabled' : '') + '>' + icon('chevron-right') + '</button>';

        container.innerHTML =
            '<div class="pagination-info">Showing ' + from + '\u2013' + to + ' of ' + total + '</div>' +
            '<div class="pagination-controls">' + buttons + '</div>';

        qsa('[data-page]', container).forEach(function (button) {
            button.addEventListener('click', function () {
                var target = Number(button.getAttribute('data-page'));
                if (target >= 1 && target <= last && target !== page) {
                    onPage(target);
                }
            });
        });
        refreshIcons(container);
    }

    function applyPermissions(scope) {
        qsa('[data-permission]', scope).forEach(function (element) {
            var required = element.getAttribute('data-permission');
            if (!required) {
                return;
            }
            if (window.APP.has(required)) {
                if (element.getAttribute('data-perm-hidden') === '1') {
                    element.removeAttribute('data-perm-hidden');
                    element.classList.remove('hidden');
                    if (element.tagName === 'BUTTON') {
                        element.disabled = false;
                    }
                }
                return;
            }
            element.setAttribute('data-perm-hidden', '1');
            element.classList.add('hidden');
            if (element.tagName === 'BUTTON') {
                element.disabled = true;
            }
        });
    }

    function setPasswordVisibility(button, show) {
        var selector = button.getAttribute('data-password-toggle');
        var input = selector ? qs(selector) : null;
        if (!input) {
            return;
        }
        var visible = typeof show === 'boolean' ? show : input.type === 'password';
        input.type = visible ? 'text' : 'password';
        var label = visible ? 'Hide password' : 'Show password';
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        var iconNode = button.querySelector('i[data-lucide], svg');
        // Re-render eye / eye-off deterministically.
        button.innerHTML = '<i data-lucide="' + (visible ? 'eye-off' : 'eye') + '" aria-hidden="true"></i>';
        refreshIcons(button);
        if (document.activeElement !== button) {
            try { input.focus({ preventScroll: true }); } catch (error) { try { input.focus(); } catch (e) {} }
            try {
                var length = input.value.length;
                input.setSelectionRange(length, length);
            } catch (error) { /* non-text inputs */ }
        }
    }

    function bindPasswordToggles(scope) {
        qsa('[data-password-toggle]', scope).forEach(function (button) {
            if (button.getAttribute('data-toggle-bound') === '1') {
                return;
            }
            button.setAttribute('data-toggle-bound', '1');
            if (button.tagName === 'BUTTON' && !button.getAttribute('type')) {
                button.setAttribute('type', 'button');
            }
            if (!button.getAttribute('aria-label')) {
                button.setAttribute('aria-label', 'Show password');
            }
            if (!button.getAttribute('title')) {
                button.setAttribute('title', button.getAttribute('aria-label'));
            }
            button.setAttribute('aria-pressed', 'false');
            button.addEventListener('click', function () {
                setPasswordVisibility(button);
            });
        });
    }

    function init() {
        var shell = qs('.app-shell');        var toggle = qs('[data-sidebar-toggle]');
        if (shell && toggle) {
            toggle.addEventListener('click', function () {
                shell.classList.toggle('sidebar-open');
            });
        }
        var backdrop = qs('.sidebar-backdrop');
        if (shell && backdrop) {
            backdrop.addEventListener('click', function () {
                shell.classList.remove('sidebar-open');
            });
        }

        qsa('[data-menu-toggle]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                var dropdown = button.closest('.dropdown');
                var wasOpen = dropdown.classList.contains('is-open');
                qsa('.dropdown.is-open').forEach(function (item) { item.classList.remove('is-open'); });
                if (!wasOpen) {
                    dropdown.classList.add('is-open');
                }
            });
        });
        document.addEventListener('click', function () {
            qsa('.dropdown.is-open').forEach(function (item) { item.classList.remove('is-open'); });
        });

        qsa('[data-modal-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(button.getAttribute('data-modal-open'));
            });
        });
        qsa('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(button.closest('.modal'));
            });
        });
        qsa('.modal').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });

        bindPasswordToggles(document);

        // Re-bind toggles injected later (modals, dynamic forms) without
        // duplicating listeners, and keep icons deterministic. Icon refresh
        // is debounced and scoped: table renders that append dozens of rows
        // must not trigger a full-document createIcons per mutation batch.
        if (typeof MutationObserver === 'function' && !init.observer) {
            try {
                var pendingScope = null;
                var pendingTimer = null;
                var flushIcons = function () {
                    pendingTimer = null;
                    var scope = pendingScope || document;
                    pendingScope = null;
                    refreshIcons(scope);
                };
                init.observer = new MutationObserver(function (mutations) {
                    var toggleScope = null;
                    var iconScope = null;
                    mutations.forEach(function (mutation) {
                        // Skip mutations caused by our own icon replacement
                        // (<i data-lucide> -> <svg>) to avoid feedback loops.
                        if (mutation.target && mutation.target.closest
                            && mutation.target.closest('svg.lucide')) {
                            return;
                        }
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType !== 1) {
                                return;
                            }
                            if (node.hasAttribute && node.hasAttribute('data-password-toggle')) {
                                toggleScope = document;
                            }
                            if (node.querySelector && node.querySelector('[data-password-toggle]')) {
                                toggleScope = node.parentNode || document;
                            }
                            if (node.querySelector && (node.hasAttribute && node.hasAttribute('data-lucide') || node.querySelector('i[data-lucide]'))) {
                                iconScope = node.parentNode || document;
                            }
                        });
                    });
                    if (toggleScope) {
                        bindPasswordToggles(toggleScope);
                    }
                    if (iconScope) {
                        pendingScope = iconScope;
                        if (pendingTimer === null) {
                            pendingTimer = window.setTimeout(flushIcons, 120);
                        }
                    }
                });
                init.observer.observe(document.documentElement, { childList: true, subtree: true });
            } catch (error) { /* observer is best-effort */ }
        }

        refreshIcons(document);
    }

    window.UI = {
        qs: qs,
        qsa: qsa,
        escape: escape,
        icon: icon,
        refreshIcons: refreshIcons,
        bindPasswordToggles: bindPasswordToggles,
        setPasswordVisibility: setPasswordVisibility,
        statusBadge: statusBadge,
        setLoading: setLoading,
        openModal: openModal,
        closeModal: closeModal,
        populate: populate,
        serialize: serialize,
        fill: fill,
        clear: clear,
        showErrors: showErrors,
        renderPagination: renderPagination,
        applyPermissions: applyPermissions,
        init: init,
        mailto: function (email) {
            return email ? 'mailto:' + email : '';
        },
        tel: function (phone) {
            return phone ? 'tel:' + String(phone).replace(/\s+/g, '') : '';
        }
    };

    document.addEventListener('DOMContentLoaded', init);
})();
