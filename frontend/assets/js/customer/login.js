(function () {
    'use strict';

    var form = UI.qs('[data-login-form]');
    var params = new URLSearchParams(window.location.search);

    function redirectFor(role) {
        var next = params.get('next');
        if (next && next.indexOf('://') === -1 && next.charAt(0) === '/') {
            window.location.href = APP.linkUrl(next);
            return;
        }
        if (role === 'customer') {
            window.location.href = APP.portalUrl || APP.route('account.dashboard');
            return;
        }
        window.location.href = APP.route('manage.dashboard');
    }

    function clearErrors() {
        UI.qsa('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
        UI.qsa('.form-error', form).forEach(function (el) { el.textContent = ''; });
    }

    function submit(event) {
        event.preventDefault();
        if (!form) {
            return;
        }
        clearErrors();
        var email = (form.email.value || '').trim().toLowerCase();
        var password = form.password.value || '';
        var clientErrors = {};
        if (!email) {
            clientErrors.email = ['Email address is required.'];
        }
        if (!password) {
            clientErrors.password = ['Password is required.'];
        }
        if (Object.keys(clientErrors).length) {
            UI.showErrors(form, clientErrors);
            return;
        }
        form.email.value = email;
        var button = form.querySelector('[data-submit]');
        UI.setLoading(button, true);
        API.post('/auth/login', {
            email: email,
            password: password
        }).then(function (data) {
            var user = data ? data.user : null;
            redirectFor(user ? user.role : 'customer');
        }).catch(function (error) {
            UI.setLoading(button, false);
            if (error && error.payload && error.payload.errors && error.payload.errors.setup_required) {
                window.location.href = APP.route('setup');
                return;
            }
            if (error && error.status === 503) {
                Alerts.error(error.message || 'Service is temporarily unavailable.');
                return;
            }
            if (error && error.errors) {
                UI.showErrors(form, error.errors);
            }
            // Keep typed email; clear password only on credential failure.
            if (error && (error.status === 401 || error.status === 409 || error.status === 403)) {
                try { form.password.value = ''; form.password.focus(); } catch (e) {}
            }
            Alerts.error(error.message || 'Unable to sign in.');
        });
    }

    function boot() {
        if (form) {
            form.addEventListener('submit', submit);
        }
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
