(function () {
    'use strict';

    var profileForm = UI.qs('[data-profile-form]');
    var passwordForm = UI.qs('[data-password-form]');

    function initials(name) {
        return APP.initials ? APP.initials(name) : String(name || '?').slice(0, 2).toUpperCase();
    }

    function fillSummary(profile) {
        var avatar = UI.qs('[data-avatar]');
        var name = UI.qs('[data-profile-name]');
        var email = UI.qs('[data-profile-email]');
        var since = UI.qs('[data-profile-since]');
        var date = UI.qs('[data-profile-date]');
        if (avatar) {
            avatar.textContent = initials(profile.name);
        }
        if (name) {
            name.textContent = profile.name;
        }
        if (email) {
            email.textContent = profile.email || '';
        }
        if (since) {
            since.textContent = profile.created_at ? APP.formatDate(profile.created_at) : '\u2014';
        }
        if (date) {
            date.textContent = profile.wedding_date ? APP.formatDate(profile.wedding_date) : '\u2014';
        }
    }

    function load() {
        API.get('/portal/profile').then(function (data) {
            var profile = data || {};
            fillSummary(profile);
            if (profileForm) {
                UI.fill(profileForm, {
                    name: profile.name || '',
                    phone: profile.phone || '',
                    wedding_date: profile.wedding_date || '',
                    event_type: profile.event_type || '',
                    address: profile.address || '',
                    city: profile.city || ''
                });
            }
        }).catch(function (error) {
            Alerts.error(error.message || 'Unable to load your profile.');
        });
    }

    function submitProfile(event) {
        event.preventDefault();
        var button = profileForm.querySelector('[data-submit]');
        UI.setLoading(button, true);
        API.put('/portal/profile', {
            name: profileForm.elements.name.value.trim(),
            phone: profileForm.phone.value.trim() || null,
            wedding_date: profileForm.wedding_date.value || null,
            event_type: profileForm.event_type.value.trim() || null,
            address: profileForm.address.value.trim() || null,
            city: profileForm.city.value.trim() || null
        }).then(function (data) {
            UI.setLoading(button, false);
            Alerts.success('Profile updated.');
            fillSummary(data || {});
        }).catch(function (error) {
            UI.setLoading(button, false);
            if (error.errors) {
                UI.showErrors(profileForm, error.errors);
            }
            Alerts.error(error.message || 'Unable to update your profile.');
        });
    }

    function submitPassword(event) {
        event.preventDefault();
        var button = passwordForm.querySelector('[data-submit]');
        UI.setLoading(button, true);
        API.post('/auth/change-password', {
            current_password: passwordForm.current_password.value,
            new_password: passwordForm.new_password.value
        }).then(function (response) {
            UI.setLoading(button, false);
            passwordForm.reset();
            Alerts.success(response.message || 'Password updated.');
        }).catch(function (error) {
            UI.setLoading(button, false);
            if (error.errors) {
                UI.showErrors(passwordForm, error.errors);
            }
            Alerts.error(error.message || 'Unable to change your password.');
        });
    }

    function boot() {
        load();
        if (profileForm) {
            profileForm.addEventListener('submit', submitProfile);
        }
        if (passwordForm) {
            passwordForm.addEventListener('submit', submitPassword);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
