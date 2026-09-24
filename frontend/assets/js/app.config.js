(function () {
    'use strict';

    window.APP = window.APP || {};

    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) {
        window.APP.csrfToken = meta.getAttribute('content');
    }

    window.APP.appName = window.APP.appName || 'VivaahFlow';
    window.APP.baseUrl = window.APP.baseUrl || '';
    window.APP.apiBase = window.APP.apiBase || (window.APP.baseUrl + '/api/v1');
    window.APP.apiMethod = window.APP.apiMethod || 'pathinfo';
    window.APP.currencySymbol = window.APP.currencySymbol || '\u20B9';
    window.APP.currentUser = null;

    window.APP.apiUrl = function (path) {
        var clean = String(path || '').replace(/^\/+/, '');
        var base = String(window.APP.apiBase).replace(/\/+$/, '');
        if (window.APP.apiMethod === 'pathinfo') {
            return base + '/index.php/' + clean;
        }
        return base + '/' + clean;
    };

    window.APP.linkUrl = function (link) {
        var target = String(link || '');
        if (/^https?:\/\//i.test(target)) {
            return target;
        }
        var base = String(window.APP.baseUrl || '').replace(/\/+$/, '');
        if (target.charAt(0) !== '/') {
            target = '/' + target;
        }
        return base + target;
    };

    window.APP.money = function (value) {
        var cents = Number(value) || 0;
        var amount = cents / 100;
        try {
            return window.APP.currencySymbol + amount.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } catch (error) {
            return window.APP.currencySymbol + amount.toFixed(2);
        }
    };

    window.APP.number = function (value, digits) {
        var places = typeof digits === 'number' ? digits : 0;
        return (Number(value) || 0).toLocaleString('en-IN', {
            minimumFractionDigits: places,
            maximumFractionDigits: places
        });
    };

    window.APP.formatDate = function (value, options) {
        if (!value) {
            return '\u2014';
        }
        var raw = String(value);
        var date = new Date(raw.replace(' ', 'T'));
        if (isNaN(date.getTime())) {
            return raw;
        }
        var opts = options || { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-IN', opts);
    };

    window.APP.formatDateTime = function (value) {
        if (!value) {
            return '\u2014';
        }
        var date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) {
            return String(value);
        }
        return date.toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    window.APP.timeAgo = function (value) {
        var date = new Date(String(value || '').replace(' ', 'T'));
        if (isNaN(date.getTime())) {
            return '';
        }
        var seconds = Math.floor((Date.now() - date.getTime()) / 1000);
        if (seconds < 60) {
            return 'just now';
        }
        var units = [
            ['year', 31536000],
            ['month', 2592000],
            ['day', 86400],
            ['hour', 3600],
            ['minute', 60]
        ];
        for (var i = 0; i < units.length; i++) {
            var amount = Math.floor(seconds / units[i][1]);
            if (amount >= 1) {
                return amount + ' ' + units[i][0] + (amount > 1 ? 's' : '') + ' ago';
            }
        }
        return 'just now';
    };

    window.APP.titleCase = function (value) {
        return String(value || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (letter) {
                return letter.toUpperCase();
            });
    };

    window.APP.initials = function (name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) {
            return '?';
        }
        if (parts.length === 1) {
            return parts[0].substring(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    };

    window.APP.has = function (permission) {
        var user = window.APP.currentUser;
        if (!user || !Array.isArray(user.permissions)) {
            return false;
        }
        if (user.role === 'admin') {
            return true;
        }
        if (user.permissions.indexOf(permission) !== -1) {
            return true;
        }
        var dot = permission.lastIndexOf('.');
        if (dot !== -1) {
            return user.permissions.indexOf(permission.substring(0, dot) + '.*') !== -1;
        }
        return false;
    };

    window.APP.debounce = function (fn, wait) {
        var timer;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, wait || 300);
        };
    };

    window.APP.query = function (params) {
        var search = new URLSearchParams();
        Object.keys(params || {}).forEach(function (key) {
            var value = params[key];
            if (value === undefined || value === null || value === '') {
                return;
            }
            search.append(key, value);
        });
        var string = search.toString();
        return string ? '?' + string : '';
    };

    window.APP.storage = {
        get: function (key, fallback) {
            try {
                var value = window.localStorage.getItem('vf_' + key);
                return value === null ? fallback : value;
            } catch (error) {
                return fallback;
            }
        },
        set: function (key, value) {
            try {
                window.localStorage.setItem('vf_' + key, value);
            } catch (error) {
                return;
            }
        },
        remove: function (key) {
            try {
                window.localStorage.removeItem('vf_' + key);
            } catch (error) {
                return;
            }
        }
    };

    window.APP.route = function (route, params) {
        var path = (window.APP.routes && window.APP.routes[route]) || route;
        Object.keys(params || {}).forEach(function (key) {
            path = String(path).replace('{' + key + '}', encodeURIComponent(params[key]));
        });
        var base = String(window.APP.baseUrl || '').replace(/\/+$/, '');
        if (String(path).charAt(0) !== '/') {
            return base === '' ? '/' + path : base + '/' + path;
        }
        return base + path;
    };

    window.APP.recordId = function () {
        var routeParams = window.APP.routeParams || {};
        var value = routeParams.id || routeParams.slug;
        if (value !== undefined && value !== null && value !== '') {
            return value;
        }
        var query = new URLSearchParams(window.location.search);
        return query.get('id') || query.get('slug') || '';
    };
})();