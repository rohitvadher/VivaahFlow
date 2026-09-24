(function () {
    'use strict';

    var form = document.getElementById('settings-form');
    var submit = form.querySelector('[data-submit]');
    var dropzone = document.querySelector('[data-logo-dropzone]');
    var preview = document.querySelector('[data-logo-preview]');
    var info = document.querySelector('[data-logo-info]');
    var input = document.querySelector('[data-logo-input]');
    var uploadBtn = document.querySelector('[data-logo-upload]');
    var selected = null;

    function load() {
        API.get('/settings').then(function (data) {
            UI.fill(form, data);
            if (data.logo_path) {
                var img = preview.querySelector('img');
                if (!img) {
                    img = document.createElement('img');
                    preview.insertBefore(img, preview.firstChild);
                }
                img.src = data.logo_path;
                preview.classList.remove('hidden');
                info.textContent = 'Current logo';
            } else {
                info.textContent = 'No logo uploaded yet.';
            }
        }).catch(function (error) {
            Alerts.error(error.message);
        });
    }

    window.Upload.bind({
        input: input,
        dropzone: dropzone,
        preview: preview,
        info: info,
        maxBytes: window.APP.uploadMaxBytes || undefined,
        onSelect: function (file) { selected = file; }
    });

    uploadBtn.addEventListener('click', function () {
        if (!selected) {
            Alerts.error('Choose an image first.');
            return;
        }
        var check = window.Upload.validate(selected, { maxBytes: window.APP.uploadMaxBytes || undefined });
        if (!check.valid) {
            Alerts.error(check.message);
            return;
        }
        var body = new FormData();
        body.append('logo', selected);
        UI.setLoading(uploadBtn, true);
        API.upload('POST', '/settings/logo', body).then(function () {
            UI.setLoading(uploadBtn, false);
            Alerts.success('Logo updated.');
            selected = null;
            input.value = '';
            load();
        }).catch(function (error) {
            UI.setLoading(uploadBtn, false);
            Alerts.error(error.message);
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        UI.setLoading(submit, true);
        API.put('/settings', UI.serialize(form)).then(function () {
            UI.setLoading(submit, false);
            Alerts.success('Settings saved.');
        }).catch(function (error) {
            UI.setLoading(submit, false);
            if (error.errors) { UI.showErrors(form, error.errors); }
            Alerts.error(error.message);
        });
    });

    load();
})();
