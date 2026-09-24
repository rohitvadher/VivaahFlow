/* VivaahFlow icon helper — deterministic Lucide rendering with safe fallback. */
(function () {
    'use strict';

    // Deprecated Lucide names mapped to their canonical replacements
    // (pinned to lucide@0.454.0). Keeps old markup working and prevents
    // invisible/broken icons from reaching the UI.
    var ALIASES = {
        'check-circle-2': 'circle-check-big',
        'check-circle': 'circle-check',
        'x-circle': 'circle-x',
        'help-circle': 'circle-help',
        'alert-circle': 'circle-alert',
        'alert-triangle': 'triangle-alert',
        'alert-octagon': 'octagon-alert',
        'arrow-right-circle': 'circle-arrow-right',
        'bar-chart-3': 'chart-column',
        'edit-3': 'pen-line',
        'grid': 'layout-grid'
    };

    function normalize(scope) {
        var root = scope || document;
        Object.keys(ALIASES).forEach(function (oldName) {
            var nodes = root.querySelectorAll
                ? root.querySelectorAll('i[data-lucide="' + oldName + '"], [data-lucide="' + oldName + '"]')
                : [];
            Array.prototype.forEach.call(nodes, function (node) {
                node.setAttribute('data-lucide', ALIASES[oldName]);
            });
        });
    }

    function hideUnknown(scope) {
        // After createIcons runs, any <i data-lucide> that was NOT replaced
        // means the name is invalid for the pinned version. Hide it so a bad
        // icon cannot visibly break layout (no empty placeholder boxes).
        var root = scope || document;
        var nodes = root.querySelectorAll ? root.querySelectorAll('i[data-lucide]') : [];
        Array.prototype.forEach.call(nodes, function (node) {
            node.setAttribute('aria-hidden', 'true');
            if (!node.getAttribute('class')) {
                node.setAttribute('class', 'icon-missing');
            }
        });
    }

    function refresh(scope) {
        try {
            normalize(scope);
        } catch (e) { /* never break rendering */ }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try {
                window.lucide.createIcons();
            } catch (e) { /* ignore icon failures */ }
        }
        try {
            hideUnknown(scope);
        } catch (e) { /* ignore */ }
    }

    function icon(name, className) {
        var canonical = ALIASES[name] || name;
        return '<i data-lucide="' + String(canonical).replace(/"/g, '') + '" class="' + (className || '') + '" aria-hidden="true"></i>';
    }

    window.Icons = {
        refresh: refresh,
        normalize: normalize,
        icon: icon,
        aliases: ALIASES
    };

    // Normalize static markup as early as possible so the first
    // UI.init() createIcons pass already sees canonical names.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { refresh(document); });
    } else {
        refresh(document);
    }
})();
