(function () {
    'use strict';

    var DEFAULT_MAX = 3 * 1024 * 1024;
    var DEFAULT_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function humanSize(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(0) + ' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function validate(file, options) {
        options = options || {};
        var max = options.maxBytes || DEFAULT_MAX;
        var types = options.types || DEFAULT_TYPES;
        if (!file) {
            return { valid: false, message: 'No file selected.' };
        }
        if (types.length && types.indexOf(file.type) === -1) {
            return { valid: false, message: 'Unsupported file type. Use JPG, PNG or WebP.' };
        }
        if (file.size > max) {
            return { valid: false, message: 'File is too large. Maximum size is ' + humanSize(max) + '.' };
        }
        return { valid: true, message: '' };
    }

    function showPreview(file, previewEl, infoEl) {
        if (!previewEl) {
            return;
        }
        if (!file) {
            previewEl.classList.add('hidden');
            return;
        }
        var reader = new FileReader();
        reader.onload = function (event) {
            var img = previewEl.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                previewEl.insertBefore(img, previewEl.firstChild);
            }
            img.src = event.target.result;
            previewEl.classList.remove('hidden');
            if (infoEl) {
                infoEl.textContent = file.name + ' \u00B7 ' + humanSize(file.size);
            }
        };
        reader.readAsDataURL(file);
    }

    function bind(options) {
        var input = typeof options.input === 'string' ? document.getElementById(options.input) : options.input;
        var dropzone = typeof options.dropzone === 'string' ? document.getElementById(options.dropzone) : options.dropzone;
        var previewEl = typeof options.preview === 'string' ? document.getElementById(options.preview) : options.preview;
        var infoEl = typeof options.info === 'string' ? document.getElementById(options.info) : options.info;

        function accept(file) {
            var result = validate(file, options);
            if (!result.valid) {
                if (window.Alerts) {
                    window.Alerts.error(result.message);
                }
                return;
            }
            showPreview(file, previewEl, infoEl);
            if (typeof options.onSelect === 'function') {
                options.onSelect(file);
            }
        }

        if (input) {
            input.addEventListener('change', function () {
                if (input.files && input.files[0]) {
                    accept(input.files[0]);
                }
            });
        }

        if (dropzone) {
            dropzone.addEventListener('click', function () {
                if (input) {
                    input.click();
                }
            });
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', function (event) {
                var files = event.dataTransfer && event.dataTransfer.files;
                if (files && files[0]) {
                    if (input) {
                        try {
                            input.files = files;
                        } catch (error) {
                            return accept(files[0]);
                        }
                    }
                    accept(files[0]);
                }
            });
        }

        return { accept: accept, showPreview: showPreview };
    }

    window.Upload = {
        bind: bind,
        validate: validate,
        showPreview: showPreview,
        humanSize: humanSize
    };
})();
