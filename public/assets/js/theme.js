(function () {
    'use strict';

    var STORAGE_KEY = 'rs_theme';
    var html = document.documentElement;

    function applyTheme(theme) {
        if (theme === 'light') {
            html.setAttribute('data-theme', 'light');
        } else {
            html.removeAttribute('data-theme');
        }
    }

    function updateIcon(theme) {
        var btn = document.querySelector('[data-theme-toggle]');
        if (!btn) return;
        /* Icon glyph itself is rendered via CSS (.theme-icon::before) so it is
           correct on first paint; here we only keep the label accessible. */
        btn.setAttribute('aria-label', theme === 'light' ? 'Включить тёмную тему' : 'Включить светлую тему');
    }

    function toggle() {
        var current = html.getAttribute('data-theme');
        var next = current === 'light' ? 'dark' : 'light';
        applyTheme(next);
        localStorage.setItem(STORAGE_KEY, next);
        updateIcon(next);
    }

    var saved = localStorage.getItem(STORAGE_KEY) || 'light';
    applyTheme(saved);

    /* Delegated + guarded: Turbo Drive swaps the <body> on navigation, so a
       handler bound to a concrete button would die with the old page. */
    if (!window.__rsThemeBound) {
        window.__rsThemeBound = true;
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-theme-toggle]')) { toggle(); }
        });
        document.addEventListener('DOMContentLoaded', function () { updateIcon(saved); });
        document.addEventListener('turbo:load', function () {
            updateIcon(html.getAttribute('data-theme') === 'light' ? 'light' : 'dark');
        });
    }
}());
