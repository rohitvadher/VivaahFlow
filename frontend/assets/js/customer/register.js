(function () {
    'use strict';

    var form = UI.qs('[data-register-form]');

    function checkRegistration(settings) {
        if (!settings) {
            return;
        }
        var open = settings.registration_open;
        var closed = open === false || open === 0 || open === '0' || open === 'false' || open === 'no' || open === 'off';
        if (closed && form) {
            form.hidden = true;
            var closedBox = UI.qs('[data-registration-closed]');
            if (closedBox) {
                closedBox.hidden = false;
            }
        }
    }

    function clearErrors() {
        if (!form) {
            return;
        }
        UI.qsa('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
        UI.qsa('.form-error', form).forEach(function (el) { el.textContent = ''; });
    }

    function submit(event) {
        event.preventDefault();
        if (!form) {
            return;
        }
        clearErrors();
        var button = form.querySelector('[data-submit]');
        var payload = {
            name: (form.elements.name.value || '').trim(),
            email: (form.elements.email ? (form.elements.email.value || '').trim().toLowerCase() : ''),
            phone: (form.elements.phone ? (form.elements.phone.value || '').trim() : '') || null,
            password: form.elements.password ? form.elements.password.value : '',
            wedding_date: (form.elements.wedding_date && form.elements.wedding_date.value) || null,
            event_type: (form.elements.event_type ? (form.elements.event_type.value || '').trim() : '') || null
        };
        if (form.elements.email) {
            form.elements.email.value = payload.email;
        }
        UI.setLoading(button, true);
        API.post('/auth/register', payload).then(function () {
            window.location.href = APP.portalUrl || APP.route('account.dashboard');
        }).catch(function (error) {
            UI.setLoading(button, false);
            if (error && error.payload && error.payload.errors && error.payload.errors.setup_required) {
                window.location.href = APP.route('setup');
                return;
            }
            if (error && error.errors) {
                UI.showErrors(form, error.errors);
                // Focus first invalid field for keyboard users.
                var first = form.querySelector('.is-invalid');
                if (first) {
                    try { first.focus(); } catch (e) {}
                }
            }
            var message = (error && error.message) || 'Unable to create your account.';
            if (error && error.status === 409) {
                message = message + ' If you already have an account, try signing in.';
            }
            Alerts.error(message);
        });
    }

    function boot() {
        if (form) {
            form.addEventListener('submit', submit);
        }
        if (window.Icons) {
            window.Icons.refresh(document);
        }
        if (APP.siteSettings) {
            checkRegistration(APP.siteSettings);
        } else if (window.Site && Site.settings) {
            Site.settings().then(checkRegistration);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
