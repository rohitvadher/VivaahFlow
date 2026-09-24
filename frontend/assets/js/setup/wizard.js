(function () {
    'use strict';

    var form = UI.qs('[data-setup-form]');
    var statusBox = UI.qs('[data-setup-status-text]');
    var statusWrap = UI.qs('[data-setup-status]');
    var steps = UI.qs('[data-setup-steps]');
    var summary = UI.qs('[data-setup-summary]');
    var doneBox = UI.qs('[data-setup-done]');
    var doneText = UI.qs('[data-setup-done-text]');
    var installedBox = UI.qs('[data-setup-installed]');
    var currentPane = 1;

    function setStatus(message, kind) {
        if (statusBox) {
            statusBox.textContent = message;
        }
        if (statusWrap) {
            statusWrap.className = 'alert alert-' + (kind || 'info');
        }
        if (window.Icons) {
            window.Icons.refresh(document);
        }
    }

    function showPane(number) {
        currentPane = number;
        UI.qsa('[data-pane]').forEach(function (pane) {
            pane.hidden = Number(pane.getAttribute('data-pane')) !== number;
        });
        UI.qsa('[data-step]').forEach(function (step) {
            var n = Number(step.getAttribute('data-step'));
            step.classList.toggle('is-active', n === number);
            step.classList.toggle('is-done', n < number);
        });
        if (number === 3) {
            renderSummary();
        }
        if (window.Icons) {
            window.Icons.refresh(document);
        }
    }

    function field(name) {
        return form ? form.elements[name] : null;
    }

    function value(name) {
        var el = field(name);
        if (!el) {
            return '';
        }
        if (el.type === 'checkbox') {
            return el.checked ? el.value : '';
        }
        return (el.value || '').trim();
    }

    function renderSummary() {
        if (!summary) {
            return;
        }
        var withDemo = field('with_demo') && field('with_demo').checked;
        var rows = [
            ['Business', value('company_name') || '—'],
            ['Email', value('company_email') || '—'],
            ['Phone', value('company_phone') || '—'],
            ['City', value('company_city') || '—'],
            ['Admin', (value('admin_name') || '—') + ' · ' + (value('admin_email') || '—')],
            ['Demo data', withDemo ? 'Yes — sample catalogue and accounts' : 'No — clean install']
        ];
        summary.innerHTML = rows.map(function (row) {
            return '<div class="detail-row" style="display:flex;justify-content:space-between;gap:14px;padding:6px 0;border-bottom:1px solid var(--ink-100)">' +
                '<dt style="color:var(--ink-500)">' + UI.escape(row[0]) + '</dt>' +
                '<dd style="font-weight:600;text-align:right">' + UI.escape(row[1]) + '</dd></div>';
        }).join('');
    }

    function validatePane(number) {
        if (!form) {
            return true;
        }
        UI.qsa('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
        UI.qsa('.form-error', form).forEach(function (el) { el.textContent = ''; });
        var errors = {};
        if (number === 1) {
            if (!value('company_name') || value('company_name').length < 2) {
                errors.company_name = ['Business name is required.'];
            }
            var email = value('company_email');
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.company_email = ['Enter a valid email address.'];
            }
        }
        if (number === 2) {
            if (!value('admin_name') || value('admin_name').length < 3) {
                errors.admin_name = ['Admin name is required (min 3 characters).'];
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value('admin_email'))) {
                errors.admin_email = ['Enter a valid admin email.'];
            }
            if ((value('admin_password') || '').length < 6) {
                errors.admin_password = ['Password must be at least 6 characters.'];
            }
            if (value('admin_password') !== value('admin_password_confirm')) {
                errors.admin_password_confirm = ['Password confirmation does not match.'];
            }
        }
        if (Object.keys(errors).length) {
            UI.showErrors(form, errors);
            var first = form.querySelector('.is-invalid');
            if (first) {
                try { first.focus(); } catch (e) {}
            }
            return false;
        }
        return true;
    }

    function serialize() {
        var data = {};
        UI.qsa('input', form).forEach(function (input) {
            if (!input.name) {
                return;
            }
            if (input.type === 'checkbox') {
                data[input.name] = input.checked ? '1' : '0';
                return;
            }
            data[input.name] = (input.value || '').trim();
        });
        data.with_demo = field('with_demo') && field('with_demo').checked ? '1' : '0';
        data.registration_open = field('registration_open') && field('registration_open').checked ? '1' : '0';
        return data;
    }

    function applyStatus(state) {
        state = state || {};
        if (state.installed) {
            setStatus('VivaahFlow is already installed. Setup is locked.', 'success');
            if (form) {
                form.hidden = true;
            }
            if (steps) {
                steps.hidden = true;
            }
            if (installedBox) {
                installedBox.hidden = false;
            }
            return;
        }
        var detail = (state.details && state.details.message) || '';
        var label = {
            server_unavailable: 'Database server unavailable. Start MySQL/MariaDB, then continue — the installer will create the vivaahflow database automatically.',
            database_missing: 'Fresh installation detected. The installer will create the vivaahflow database and schema automatically.',
            schema_incomplete: 'Database exists but schema is incomplete. Continuing will install the missing tables safely.',
            not_installed: 'Database is ready. Complete the business profile and admin account below.'
        }[state.status] || 'Complete the steps below to finish installation.';
        setStatus(label + (detail ? ' ' + detail : ''), state.status === 'server_unavailable' ? 'warning' : 'info');
        if (steps) {
            steps.hidden = false;
        }
        showPane(1);
    }

    function boot() {
        UI.qsa('[data-next]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = Number(button.getAttribute('data-next'));
                if (validatePane(currentPane)) {
                    showPane(target);
                }
            });
        });
        UI.qsa('[data-back]').forEach(function (button) {
            button.addEventListener('click', function () {
                showPane(Number(button.getAttribute('data-back')));
            });
        });
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!validatePane(1) || !validatePane(2)) {
                    showPane(!validatePane(1) ? 1 : 2);
                    return;
                }
                renderSummary();
                var button = form.querySelector('[data-submit]');
                UI.setLoading(button, true);
                setStatus('Installing… creating database, schema and admin account. This may take a few seconds.', 'info');
                API.post('/setup/install', serialize()).then(function (data) {
                    UI.setLoading(button, false);
                    form.hidden = true;
                    if (steps) {
                        steps.hidden = true;
                    }
                    if (statusWrap) {
                        statusWrap.hidden = true;
                    }
                    if (doneBox) {
                        doneBox.hidden = false;
                    }
                    var demo = data && data.summary && data.summary.demo;
                    if (doneText) {
                        doneText.textContent = 'Database created, schema installed, admin created' +
                            (demo ? ' and demo data installed.' : '.') +
                            ' You can now sign in.';
                    }
                    Alerts.success('VivaahFlow installed successfully.');
                    if (window.Icons) {
                        window.Icons.refresh(document);
                    }
                }).catch(function (error) {
                    UI.setLoading(button, false);
                    if (error && error.errors) {
                        UI.showErrors(form, error.errors);
                        showPane(form.querySelector('[data-pane="1"] .is-invalid') ? 1 : 2);
                    }
                    setStatus((error && error.message) || 'Installation failed.', 'warning');
                    Alerts.error((error && error.message) || 'Installation failed.');
                });
            });
        }
        var initial = window.VIVAAH_SETUP_INITIAL || null;
        if (initial) {
            applyStatus(initial);
        }
        API.get('/setup/status').then(function (data) {
            applyStatus(data);
        }).catch(function () {
            // Keep server-rendered initial state when API is unreachable.
        });
        if (window.Icons) {
            window.Icons.refresh(document);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
