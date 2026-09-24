(function () {
    'use strict';

    var doc = document;

    function resolve(el) {
        if (!el) {
            return null;
        }
        if (typeof el === 'string') {
            return doc.querySelector(el);
        }
        return el && el.nodeType ? el : null;
    }

    var pending = 0;
    var shown = false;
    var progressEl = null;
    var showTimer = null;
    var hideTimer = null;

    function progress() {
        if (!progressEl) {
            progressEl = doc.createElement('div');
            progressEl.className = 'page-progress';
            doc.body.appendChild(progressEl);
        }
        return progressEl;
    }

    function scheduleShow() {
        clearTimeout(showTimer);
        showTimer = setTimeout(function () {
            if (pending > 0 && !shown) {
                shown = true;
                progress().classList.add('is-active');
            }
        }, 160);
    }

    function scheduleHide() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            if (pending === 0 && shown) {
                shown = false;
                progress().classList.remove('is-active');
            }
        }, 350);
    }

    function track() {
        pending += 1;
        scheduleShow();
        var released = false;
        return function () {
            if (released) {
                return;
            }
            released = true;
            pending = Math.max(0, pending - 1);
            scheduleHide();
        };
    }

    function wrapApi() {
        if (wrapApi.done) {
            return;
        }
        wrapApi.done = true;
        var api = window.API;
        if (!api || typeof api.request !== 'function') {
            return;
        }
        ['request', 'get', 'post', 'put', 'patch', 'del', 'upload', 'me', 'csrf', 'login', 'logout'].forEach(function (name) {
            if (typeof api[name] !== 'function') {
                return;
            }
            var original = api[name];
            api[name] = function () {
                var release = track();
                var args = Array.prototype.slice.call(arguments);
                return original.apply(this, args).then(function (value) {
                    release();
                    return value;
                }, function (error) {
                    release();
                    throw error;
                });
            };
        });
    }

    function autoPage() {
        if (doc.readyState === 'loading') {
            var release = track();
            window.addEventListener('load', release, { once: true });
        }
    }

    function spinnerHtml() {
        return '<div class="loader-block"><div class="spinner"></div></div>';
    }

    function show(el) {
        var target = resolve(el);
        if (!target) {
            return;
        }
        target.classList.add('is-loading-region');
        if (!target.querySelector('.loader-block')) {
            target.insertAdjacentHTML('beforeend', spinnerHtml());
        }
    }

    function hide(el) {
        var target = resolve(el);
        if (!target) {
            return;
        }
        target.classList.remove('is-loading-region');
        var overlay = target.querySelector('.loader-block');
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    }

    function skeletonHtml(options) {
        var opts = options || {};
        var rows = Math.max(1, Number(opts.rows) || 3);
        var height = opts.height || null;
        var bars = '';
        for (var i = 0; i < rows; i += 1) {
            var style = height ? ' style="height:' + (typeof height === 'number' ? height + 'px' : height) + '"' : '';
            bars += '<div class="skeleton skeleton-row"' + style + '></div>';
        }
        return '<div class="loader-skeleton' + (opts.full ? ' is-full' : '') + '">' + bars + '</div>';
    }

    function skeleton(el, options) {
        var target = resolve(el);
        if (!target) {
            return;
        }
        target.innerHTML = skeletonHtml(options);
    }

    function emptyHtml(options) {
        var opts = options || {};
        return '<div class="table-empty">' +
            window.UI.icon(opts.icon || 'inbox') +
            '<div class="empty-title">' + window.UI.escape(opts.title || 'Nothing here yet') + '</div>' +
            (opts.hint ? '<div class="text-sm mt-1">' + window.UI.escape(opts.hint) + '</div>' : '') +
            '</div>';
    }

    function empty(el, options) {
        var target = resolve(el);
        if (!target) {
            return;
        }
        target.innerHTML = emptyHtml(options);
    }

    function button(btn, loading) {
        var target = resolve(btn);
        if (!target) {
            return;
        }
        if (loading) {
            target.classList.add('is-loading');
            target.disabled = true;
        } else {
            target.classList.remove('is-loading');
            target.disabled = false;
        }
    }

    function wrap(promise, el, options) {
        var opts = options || {};
        var target = resolve(el);
        var release = null;
        if (target) {
            target.innerHTML = skeletonHtml({ rows: opts.rows || 3, height: opts.height });
        } else {
            release = track();
        }
        return Promise.resolve(promise).then(function (value) {
            if (release) {
                release();
            }
            return value;
        }, function (error) {
            if (release) {
                release();
            }
            throw error;
        });
    }

    wrapApi();
    autoPage();

    window.Loader = {
        page: {
            watchApi: wrapApi,
            track: track
        },
        watchApi: wrapApi,
        track: track,
        show: show,
        hide: hide,
        skeleton: skeleton,
        skeletonHtml: skeletonHtml,
        empty: empty,
        emptyHtml: emptyHtml,
        button: button,
        wrap: wrap
    };
})();