(function () {
    'use strict';

    var form = document.getElementById('login-form');
    if (!form) {
        return;
    }

    var submit = form.querySelector('[type="submit"]');

    function clearErrors() {
        UI.qsa('.form-control', form).forEach(function (field) {
            field.classList.remove('is-invalid');
        });
        UI.qsa('.form-error', form).forEach(function (el) { el.textContent = ''; });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearErrors();

        var email = (form.email.value || '').trim().toLowerCase();
        var password = form.password.value || '';
        var errors = {};
        if (!email) {
            errors.email = ['Email address is required.'];
        }
        if (!password) {
            errors.password = ['Password is required.'];
        }
        if (Object.keys(errors).length) {
            UI.showErrors(form, errors);
            return;
        }
        form.email.value = email;

        UI.setLoading(submit, true);
        API.login(email, password).then(function (data) {
            if (data && data.user && data.user.role === 'customer') {
                window.location.href = window.APP.route('account.dashboard');
                return;
            }
            window.location.href = window.APP.route('manage.dashboard');
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error && error.errors) {
                UI.showErrors(form, error.errors);
            }
            if (error && (error.status === 401 || error.status === 403)) {
                try { form.password.value = ''; form.password.focus(); } catch (e) {}
            }
            Alerts.error((error && error.message) || 'Unable to sign in.');
        });
    });

    if (window.Icons) {
        window.Icons.refresh(document);
    }
})();
