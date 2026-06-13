/* Richsound — mobile full-screen player.
   Pure view layer over player.js: reads state via window.RichsoundPlayer,
   reacts to rs:* document events + audio events. Holds no playback state.

   Turbo Drive aware: boots once; on every body swap Turbo re-executes this
   script and the guard below routes into _rebind(), which re-queries the
   fresh #mobilePlayer markup and re-attaches all handlers. */
(function () {
    'use strict';

    if (window.RichsoundMobilePlayer) {
        window.RichsoundMobilePlayer._rebind();
        return;
    }

    var audio = document.querySelector('[data-player-audio]');
    var api   = window.RichsoundPlayer;
    if (!audio || !api) { return; }

    var seeking = false;
    var drag    = { active: false, startY: 0, lastY: 0, lastT: 0, velocity: 0 };

    /* fresh DOM refs, refilled by rebind() on every page visit */
    var m = {};

    /* ── helpers ─────────────────────────────────────────────────── */

    function fmt(total) {
        var s = Math.max(0, Math.floor(total || 0));
        var mm = Math.floor(s / 60);
        var r = s % 60;
        return mm + ':' + (r < 10 ? '0' : '') + r;
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function currentTrack() {
        return api.getPlaylist()[api.getCurrentIndex()] || null;
    }

    function isOpen() {
        return !!m.panel && m.panel.classList.contains('mobile-player--open');
    }

    /* ── open / close ────────────────────────────────────────────── */

    function openPlayer() {
        if (!m.panel) { return; }
        m.panel.removeAttribute('aria-hidden');
        m.panel.classList.add('mobile-player--open');
        document.body.classList.add('mplayer-open');
        syncAll();
        if (m.closeBtn) { m.closeBtn.focus(); }
    }

    function closePlayer() {
        if (!m.panel) { return; }
        m.panel.setAttribute('aria-hidden', 'true');
        m.panel.classList.remove('mobile-player--open');
        document.body.classList.remove('mplayer-open');
    }

    /* ── sync functions ──────────────────────────────────────────── */

    function syncTrackInfo() {
        var track = currentTrack();
        if (m.title)  { m.title.textContent  = track ? track.title  : 'Трек не выбран'; }
        if (m.artist) { m.artist.textContent = track ? track.artist : '—'; }

        var url = track && track.coverUrl ? track.coverUrl : '';
        if (m.cover) { m.cover.style.backgroundImage = url ? 'url("' + url + '")' : ''; }
        if (m.bg)    { m.bg.style.backgroundImage    = url ? 'url("' + url + '")' : ''; }
    }

    function syncProgress() {
        if (!m.panel) { return; }
        var dur = isFinite(audio.duration)    ? audio.duration    : 0;
        var cur = isFinite(audio.currentTime) ? audio.currentTime : 0;
        var pct = dur > 0 ? Math.min(100, (cur / dur) * 100) : 0;

        if (!seeking) {
            if (m.fill) { m.fill.style.width = pct + '%';   }
            if (m.seek) { m.seek.value       = String(pct); }
        }
        if (m.current)  { m.current.textContent  = fmt(cur); }
        if (m.duration) { m.duration.textContent = fmt(dur); }
    }

    function syncPlayState() {
        if (!m.panel) { return; }
        m.panel.classList.toggle('mobile-player--playing', !audio.paused && !!audio.src);
    }

    function syncModes(detail) {
        if (m.shuffle) { m.shuffle.classList.toggle('is-active', detail.shuffle === true); }
        if (m.repeat) {
            m.repeat.classList.toggle('is-active',     detail.repeat === 'all' || detail.repeat === 'one');
            m.repeat.classList.toggle('is-repeat-one', detail.repeat === 'one');
        }
    }

    function syncHeart() {
        if (!m.heart) { return; }
        if (!api.isAuth()) { m.heart.hidden = true; return; }
        var track = currentTrack();
        m.heart.hidden = !track;
        if (!track) { return; }
        var liked = api.isLiked(track.id);
        m.heart.classList.toggle('is-liked', liked);
        m.heart.setAttribute('aria-label', liked ? 'Убрать из избранного' : 'В избранное');
        var svg = m.heart.querySelector('svg');
        if (svg) { svg.setAttribute('fill', liked ? 'currentColor' : 'none'); }
    }

    function syncQueue() {
        if (!m.queueList) { return; }
        var playlist = api.getPlaylist();
        var cur      = api.getCurrentIndex();

        m.queueList.innerHTML = '';

        if (playlist.length <= 1) {
            var empty = document.createElement('p');
            empty.className = 'mplayer__queue-empty';
            empty.textContent = 'Очередь пуста';
            m.queueList.appendChild(empty);
            return;
        }

        /* Upcoming tracks starting after the current one, wrapping around */
        for (var step = 1; step < playlist.length; step++) {
            var idx   = (cur + step) % playlist.length;
            var track = playlist[idx];

            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'mplayer__queue-item';
            item.setAttribute('data-queue-index', idx);
            item.innerHTML =
                '<div class="mplayer__queue-cover"' +
                    (track.coverUrl ? ' style="background-image:url(\'' + escHtml(track.coverUrl) + '\')"' : '') +
                '></div>' +
                '<div class="mplayer__queue-info">' +
                    '<p class="mplayer__queue-track">'  + escHtml(track.title)  + '</p>' +
                    '<p class="mplayer__queue-artist">' + escHtml(track.artist) + '</p>' +
                '</div>' +
                '<span class="mplayer__queue-dur">' + fmt(track.duration) + '</span>';

            item.addEventListener('click', function () {
                api.play(Number(this.getAttribute('data-queue-index')));
            });
            m.queueList.appendChild(item);
        }
    }

    function syncAll() {
        syncTrackInfo();
        syncProgress();
        syncPlayState();
        syncHeart();
        syncQueue();
    }

    /* ── three-dots menu ─────────────────────────────────────────── */

    function openMenu() {
        if (!m.menu) { return; }
        var track = currentTrack();
        if (!track) { return; }
        if (m.menuTrack)     { m.menuTrack.textContent = track.title + ' — ' + track.artist; }
        if (m.menuPlaylists) { m.menuPlaylists.hidden = true; m.menuPlaylists.innerHTML = ''; }

        /* Hide actions that can't work for this track / user */
        var plBtn = m.menu.querySelector('[data-mplayer-action="playlist"]');
        if (plBtn) { plBtn.hidden = !api.isAuth() || api.getUserPlaylists().length === 0; }
        var auBtn = m.menu.querySelector('[data-mplayer-action="author"]');
        if (auBtn) { auBtn.hidden = !(Number(track.authorId) > 0); }

        m.menu.hidden = false;
        /* Separate task so the transition runs after `hidden` is flushed */
        setTimeout(function () { m.menu.classList.add('mplayer__menu--open'); }, 10);
    }

    function closeMenu() {
        if (!m.menu) { return; }
        var menuEl = m.menu;
        menuEl.classList.remove('mplayer__menu--open');
        setTimeout(function () { menuEl.hidden = true; }, 200);
    }

    function showPlaylistChoices() {
        if (!m.menuPlaylists) { return; }
        m.menuPlaylists.innerHTML = '';
        api.getUserPlaylists().forEach(function (pl) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'mplayer__menu-playlist';
            b.textContent = pl.title;
            b.addEventListener('click', function () {
                api.addToPlaylist(pl.id).then(function (added) {
                    b.textContent = (added ? '✓ ' : '') + pl.title;
                    setTimeout(closeMenu, 450);
                });
            });
            m.menuPlaylists.appendChild(b);
        });
        m.menuPlaylists.hidden = false;
    }

    function shareTrack() {
        var track = currentTrack();
        if (!track) { return; }
        var url = location.origin + '/tracks/' + track.id;
        if (navigator.share) {
            navigator.share({ title: track.title, text: track.title + ' — ' + track.artist, url: url })
                .catch(function () {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(url).catch(function () {});
        }
        closeMenu();
    }

    /* ── per-page wiring (boot + every Turbo visit) ──────────────── */

    function rebind() {
        var panel = document.getElementById('mobilePlayer');
        var root  = document.querySelector('[data-player-root]');

        m = {
            panel:         panel,
            root:          root,
            content:       panel ? panel.querySelector('.mplayer__content')              : null,
            title:         panel ? panel.querySelector('[data-mplayer-title]')           : null,
            artist:        panel ? panel.querySelector('[data-mplayer-artist]')          : null,
            cover:         panel ? panel.querySelector('[data-mplayer-cover]')           : null,
            bg:            panel ? panel.querySelector('[data-mplayer-bg]')              : null,
            fill:          panel ? panel.querySelector('[data-mplayer-fill]')            : null,
            current:       panel ? panel.querySelector('[data-mplayer-current]')         : null,
            duration:      panel ? panel.querySelector('[data-mplayer-duration]')        : null,
            seek:          panel ? panel.querySelector('[data-mplayer-seek]')            : null,
            toggle:        panel ? panel.querySelector('[data-mplayer-toggle]')          : null,
            prev:          panel ? panel.querySelector('[data-mplayer-prev]')            : null,
            next:          panel ? panel.querySelector('[data-mplayer-next]')            : null,
            shuffle:       panel ? panel.querySelector('[data-mplayer-shuffle]')         : null,
            repeat:        panel ? panel.querySelector('[data-mplayer-repeat]')          : null,
            heart:         panel ? panel.querySelector('[data-mplayer-heart]')           : null,
            queueList:     panel ? panel.querySelector('[data-mplayer-queue-list]')      : null,
            queueBtn:      panel ? panel.querySelector('[data-mplayer-queue-btn]')       : null,
            queueSection:  panel ? panel.querySelector('.mplayer__queue')                : null,
            more:          panel ? panel.querySelector('.mplayer__more')                 : null,
            menu:          panel ? panel.querySelector('[data-mplayer-menu]')            : null,
            closeBtn:      document.getElementById('mobilePlayerClose')
        };
        m.menuTrack     = m.menu ? m.menu.querySelector('[data-mplayer-menu-track]')     : null;
        m.menuPlaylists = m.menu ? m.menu.querySelector('[data-mplayer-menu-playlists]') : null;

        /* The previous page's body (and its `mplayer-open` class) is gone,
           but make sure scroll isn't locked by a stale class. */
        document.body.classList.remove('mplayer-open');
        seeking     = false;
        drag.active = false;

        if (!panel) { return; }

        /* Mini player: tap on now-playing opens the sheet */
        var miniNow = root ? root.querySelector('.player__now') : null;
        if (miniNow)   { miniNow.addEventListener('click', openPlayer); }
        if (m.closeBtn) { m.closeBtn.addEventListener('click', closePlayer); }

        /* Interactive swipe-down to dismiss (sheet follows the finger) */
        panel.addEventListener('touchstart', function (e) {
            if (e.target.closest('input[type="range"]')) { return; }
            if (e.target.closest('[data-mplayer-menu]')) { return; }
            if (m.content && m.content.scrollTop > 4) { return; }
            drag.active   = true;
            drag.startY   = e.touches[0].clientY;
            drag.lastY    = drag.startY;
            drag.lastT    = e.timeStamp;
            drag.velocity = 0;
        }, { passive: true });

        panel.addEventListener('touchmove', function (e) {
            if (!drag.active) { return; }
            var y  = e.touches[0].clientY;
            var dy = y - drag.startY;
            var dt = e.timeStamp - drag.lastT;
            if (dt > 0) { drag.velocity = (y - drag.lastY) / dt; }
            drag.lastY = y;
            drag.lastT = e.timeStamp;
            if (dy > 0) {
                panel.style.transition = 'none';
                panel.style.transform  = 'translateY(' + dy + 'px)';
            }
        }, { passive: true });

        panel.addEventListener('touchend', function () {
            if (!drag.active) { return; }
            drag.active = false;
            var dy = drag.lastY - drag.startY;
            panel.style.transition = '';
            panel.style.transform  = '';
            /* Close on a long pull (>30% of screen) or a fast flick */
            if (dy > window.innerHeight * 0.3 || (dy > 60 && drag.velocity > 0.6)) {
                closePlayer();
            }
        }, { passive: true });

        /* Controls → player.js */
        if (m.toggle) {
            m.toggle.addEventListener('click', function () {
                var b = m.root && m.root.querySelector('[data-player-toggle]');
                if (b) { b.click(); }
            });
        }
        if (m.prev) {
            m.prev.addEventListener('click', function () {
                var b = m.root && m.root.querySelector('[data-player-prev]');
                if (b) { b.click(); }
            });
        }
        if (m.next) {
            m.next.addEventListener('click', function () {
                var b = m.root && m.root.querySelector('[data-player-next]');
                if (b) { b.click(); }
            });
        }
        if (m.shuffle) {
            m.shuffle.addEventListener('click', function () {
                var b = m.root && m.root.querySelector('.player__btn--shuffle');
                if (b) { b.click(); }
            });
        }
        if (m.repeat) {
            m.repeat.addEventListener('click', function () {
                var b = m.root && m.root.querySelector('.player__btn--repeat');
                if (b) { b.click(); }
            });
        }
        if (m.heart) {
            m.heart.addEventListener('click', function () { api.toggleLike(); });
        }

        /* Seek: preview while dragging, commit on release */
        if (m.seek) {
            m.seek.addEventListener('input', function () {
                seeking = true;
                if (m.fill) { m.fill.style.width = m.seek.value + '%'; }
                var dur = isFinite(audio.duration) ? audio.duration : 0;
                if (m.current && dur > 0) {
                    m.current.textContent = fmt((Number(m.seek.value) / 100) * dur);
                }
            });
            m.seek.addEventListener('change', function () {
                var dur = isFinite(audio.duration) ? audio.duration : 0;
                if (dur > 0) { audio.currentTime = (Number(m.seek.value) / 100) * dur; }
                seeking = false;
            });
        }

        /* Queue button — toggles between the queue section and the main view */
        if (m.queueBtn && m.content) {
            m.queueBtn.addEventListener('click', function () {
                if (!m.queueSection) { return; }
                m.content.scrollTo({
                    top:      queueShown() ? 0 : m.queueSection.offsetTop,
                    behavior: 'smooth'
                });
            });
            m.content.addEventListener('scroll', function () {
                m.queueBtn.classList.toggle('is-active', queueShown());
            }, { passive: true });
        }

        /* Three-dots menu */
        if (m.more && m.menu) {
            m.more.addEventListener('click', openMenu);
            m.menu.addEventListener('click', function (e) {
                if (e.target.closest('[data-mplayer-menu-close]')) { closeMenu(); return; }
                var action = e.target.closest('[data-mplayer-action]');
                if (!action) { return; }
                switch (action.getAttribute('data-mplayer-action')) {
                    case 'playlist': showPlaylistChoices(); break;
                    case 'author':
                        var track = currentTrack();
                        if (track && Number(track.authorId) > 0) {
                            var url = '/authors/' + Number(track.authorId);
                            closeMenu();
                            closePlayer();
                            /* Turbo keeps playback alive across the visit */
                            if (window.Turbo) { window.Turbo.visit(url); }
                            else              { location.href = url; }
                        }
                        break;
                    case 'share': shareTrack(); break;
                }
            });
        }

        syncAll();
    }

    /* The queue counts as "shown" once the user is past half of the
       available scroll range. A short queue can never reach the top
       of the screen (scroll clamps), so absolute offsets don't work. */
    function queueShown() {
        if (!m.content) { return false; }
        var maxScroll = m.content.scrollHeight - m.content.clientHeight;
        return maxScroll > 0 && m.content.scrollTop >= maxScroll * 0.5;
    }

    /* ── subscriptions (bound ONCE: audio node + document survive) ── */

    audio.addEventListener('play',           syncPlayState);
    audio.addEventListener('pause',          syncPlayState);
    audio.addEventListener('ended',          syncPlayState);
    audio.addEventListener('timeupdate',     syncProgress);
    audio.addEventListener('loadedmetadata', syncProgress);

    document.addEventListener('rs:trackchange', function () {
        syncTrackInfo();
        syncHeart();
        syncQueue();
    });
    document.addEventListener('rs:modechange', function (e) { syncModes(e.detail); });
    document.addEventListener('rs:likechange', function () { syncHeart(); });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !isOpen()) { return; }
        if (m.menu && !m.menu.hidden) { closeMenu(); }
        else                          { closePlayer(); }
    });

    window.RichsoundMobilePlayer = { _rebind: rebind };

    rebind();
}());
