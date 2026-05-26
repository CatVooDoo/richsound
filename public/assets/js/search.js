(function () {
    'use strict';

    var field    = document.querySelector('.js-search-field');
    var form     = document.querySelector('.js-search-form');
    var dropdown = document.querySelector('.js-search-dropdown');

    if (!field || !form || !dropdown) { return; }

    var timer  = null;
    var lastQ  = '';

    function debounce(fn, ms) {
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    function formatDuration(seconds) {
        var s = Math.max(0, parseInt(seconds, 10) || 0);
        return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderDropdown(data) {
        dropdown.innerHTML = '';
        var tracks  = data.tracks  || [];
        var authors = data.authors || [];

        if (tracks.length === 0 && authors.length === 0) {
            dropdown.hidden = true;
            return;
        }

        if (tracks.length > 0) {
            var tHead = document.createElement('p');
            tHead.className = 'search-dd__label';
            tHead.textContent = 'Треки';
            dropdown.appendChild(tHead);

            tracks.forEach(function (t) {
                var item = document.createElement('a');
                item.href = '/tracks/' + t.id;
                item.className = 'search-dd__item';
                item.innerHTML =
                    '<div class="search-dd__cover"' +
                        (t.cover_path ? ' style="background-image:url(\'' + escHtml(t.cover_path) + '\')"' : '') +
                    '></div>' +
                    '<div class="search-dd__info">' +
                        '<span class="search-dd__title">' + escHtml(t.title) + '</span>' +
                        '<span class="search-dd__meta">' + escHtml(t.author_name) + ' · ' + formatDuration(t.duration) + '</span>' +
                    '</div>';
                dropdown.appendChild(item);
            });
        }

        if (authors.length > 0) {
            var aHead = document.createElement('p');
            aHead.className = 'search-dd__label';
            aHead.textContent = 'Авторы';
            dropdown.appendChild(aHead);

            authors.forEach(function (a) {
                var item = document.createElement('a');
                item.href = '/authors/' + a.id;
                item.className = 'search-dd__item';
                var initial = a.name ? a.name.charAt(0).toUpperCase() : '?';
                item.innerHTML =
                    '<div class="search-dd__avatar"' +
                        (a.avatar ? ' style="background-image:url(\'' + escHtml(a.avatar) + '\');font-size:0"' : '') +
                    '>' + (a.avatar ? '' : escHtml(initial)) + '</div>' +
                    '<div class="search-dd__info">' +
                        '<span class="search-dd__title">' + escHtml(a.name) + '</span>' +
                        '<span class="search-dd__meta">' + a.tracks_count + ' треков · ' + a.subscribers_count + ' подписчиков</span>' +
                    '</div>';
                dropdown.appendChild(item);
            });
        }

        var all = document.createElement('a');
        all.href = '/search?q=' + encodeURIComponent(field.value.trim());
        all.className = 'search-dd__all';
        all.textContent = 'Все результаты';
        dropdown.appendChild(all);

        dropdown.hidden = false;
    }

    var doSuggest = debounce(function (q) {
        if (q.length < 2) { dropdown.hidden = true; return; }
        if (q === lastQ)  { return; }
        lastQ = q;

        fetch('/search/suggest?q=' + encodeURIComponent(q))
            .then(function (r) { return r.json(); })
            .then(renderDropdown)
            .catch(function () { dropdown.hidden = true; });
    }, 300);

    field.addEventListener('input', function () {
        doSuggest(field.value.trim());
    });

    field.addEventListener('focus', function () {
        var q = field.value.trim();
        if (q.length >= 2) { doSuggest(q); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var q = field.value.trim();
        if (q !== '') {
            window.location.href = '/search?q=' + encodeURIComponent(q);
        }
    });

    document.addEventListener('click', function (e) {
        if (!form.contains(e.target)) { dropdown.hidden = true; }
    });
}());
