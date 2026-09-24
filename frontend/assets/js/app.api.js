(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.getAttribute('content')) {
            window.APP.csrfToken = meta.getAttribute('content');
        }
        return window.APP.csrfToken || '';
    }

    function ApiError(message, status, errors, payload) {
        var error = new Error(message || 'Request failed.');
        error.name = 'ApiError';
        error.status = status || 0;
        error.errors = errors || null;
        error.payload = payload || null;
        return error;
    }

    function buildUrl(path, query) {
        return window.APP.apiUrl(path) + window.APP.query(query);
    }

    function request(method, path, options) {
        options = options || {};
        var upper = String(method || 'GET').toUpperCase();
        var headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        var init = {
            method: upper,
            headers: headers,
            credentials: 'same-origin'
        };

        if (options.formData instanceof FormData) {
            init.body = options.formData;
        } else if (options.body !== undefined && options.body !== null) {
            headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(options.body);
        }

        if (upper !== 'GET' && upper !== 'HEAD') {
            headers['X-CSRF-Token'] = csrfToken();
        }

        return fetch(buildUrl(path, options.query), init).then(function (response) {
            var contentType = response.headers.get('content-type') || '';
            var parsed = contentType.indexOf('application/json') !== -1
                ? response.json().catch(function () { return null; })
                : response.text().then(function (text) { return { message: text }; });

            return parsed.then(function (payload) {
                if (!response.ok || (payload && payload.success === false)) {
                    var message = (payload && payload.message) || ('Request failed with status ' + response.status);
                    var error = ApiError(message, response.status, payload && payload.errors ? payload.errors : null, payload);
                    if (response.status === 401) {
                        window.dispatchEvent(new CustomEvent('app:unauthorized', { detail: error }));
                        if (window.APP.redirectOn401 !== false && window.APP.loginUrl) {
                            window.setTimeout(function () {
                                window.location.href = window.APP.loginUrl;
                            }, 300);
                        }
                    }
                    if (response.status === 403) {
                        window.dispatchEvent(new CustomEvent('app:forbidden', { detail: error }));
                    }
                    throw error;
                }
                return payload ? payload.data : null;
            });
        });
    }

    window.API = {
        request: request,
        get: function (path, query) {
            return request('GET', path, { query: query });
        },
        post: function (path, body, options) {
            return request('POST', path, Object.assign({ body: body }, options || {}));
        },
        put: function (path, body, options) {
            return request('PUT', path, Object.assign({ body: body }, options || {}));
        },
        patch: function (path, body, options) {
            return request('PATCH', path, Object.assign({ body: body }, options || {}));
        },
        del: function (path, body) {
            return request('DELETE', path, body ? { body: body } : {});
        },
        upload: function (method, path, formData, options) {
            return request(method || 'POST', path, Object.assign({ formData: formData }, options || {}));
        },
        me: function () {
            return request('GET', '/auth/me');
        },
        csrf: function () {
            return request('GET', '/auth/csrf');
        },
        login: function (email, password) {
            return request('POST', '/auth/login', { body: { email: email, password: password } });
        },
        logout: function () {
            return request('POST', '/auth/logout');
        }
    };
})();
