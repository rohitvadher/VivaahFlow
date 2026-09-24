(function () {
    'use strict';

    var summary = document.getElementById('profile-summary');
    var profileForm = document.getElementById('profile-form');
    var profileSubmit = profileForm.querySelector('[data-submit]');
    var passwordForm = document.getElementById('password-form');
    var passwordSubmit = passwordForm.querySelector('[data-submit]');

    function renderSummary(user) {
        summary.innerHTML = '<div class="flex items-center gap-3 mb-4">' +
            '<span class="avatar">' + UI.escape(window.APP.initials(user.name)) + '</span>' +
            '<div><div class="font-semibold">' + UI.escape(user.name) + '</div>' +
            '<div class="text-muted text-sm">' + UI.escape(user.email) + '</div></div></div>' +
            '<dl class="detail-list">' +
            Workflow.detailRow('Role', UI.escape(user.role_name || window.APP.titleCase(user.role || ''))) +
            Workflow.detailRow('Permissions', window.APP.number((user.permissions || []).length)) +
            Workflow.detailRow('Status', UI.statusBadge(user.status || 'active')) +
            '</dl>';
    }

    function load() {
        API.get('/auth/me').then(function (data) {
            var user = data && data.user ? data.user : {};
            renderSummary(user);
            profileForm.name.value = user.name || '';
            profileForm.email.value = user.email || '';
            profileForm.phone.value = user.phone || '';
        }).catch(function (error) {
            summary.innerHTML = '<div class="text-muted text-sm">' + UI.escape(error.message) + '</div>';
        });
    }

    profileForm.addEventListener('submit', function (event) {
        event.preventDefault();
        UI.setLoading(profileSubmit, true);
        API.put('/auth/profile', UI.serialize(profileForm)).then(function (data) {
            UI.setLoading(profileSubmit, false);
            Alerts.success('Profile updated.');
            if (data && data.user) {
                window.APP.currentUser = data.user;
                renderSummary(data.user);
            }
        }).catch(function (error) {
            UI.setLoading(profileSubmit, false);
            if (error.errors) { UI.showErrors(profileForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    passwordForm.addEventListener('submit', function (event) {
        event.preventDefault();
        UI.setLoading(passwordSubmit, true);
        API.post('/auth/change-password', UI.serialize(passwordForm)).then(function () {
            UI.setLoading(passwordSubmit, false);
            passwordForm.reset();
            Alerts.success('Password updated.');
        }).catch(function (error) {
            UI.setLoading(passwordSubmit, false);
            if (error.errors) { UI.showErrors(passwordForm, error.errors); }
            Alerts.error(error.message);
        });
    });

    load();
})();
