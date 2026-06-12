/* Richsound — mobile full-screen player.
   Pure view layer over player.js: reads state via window.RichsoundPlayer
   and reacts to rs:* events + audio events. Holds no playback state. */
(function () {
    'use strict';

    var panel    = document.getElementById('mobilePlayer');
    var audio    = document.querySelector('[data-player-audio]');
    var mainRoot = document.querySelector('[data-player-root]');
    var api      = window.RichsoundPlayer;

    if (!panel || !audio || !mainRoot || !api) { return; }

    /* ── Element refs ───────────────────────────────────────────── */
    var mpTitle    = panel.querySelector('[data-mplayer-title]');
    var mpArtist   = panel.querySelector('[data-mplayer-artist]');
    var mpCover    = panel.querySelector('[data-mplayer-cover]');
    var mpBg       = panel.querySelector('[data-mplayer-bg]');
    var mpFill     = panel.querySelector('[data-mplayer-fill]');
    var mpCurrent  = panel.querySelector('[data-mplayer-current]');
    var mpDuration = panel.querySelector('[data-mplayer-duration]');
    var mpSeek     = panel.querySelector('[data-mplayer-seek]');
    var mpToggle   = panel.querySelector('[data-mplayer-toggle]');
    var mpPrev     = panel.querySelector('[data-mplayer-prev]');
    var mpNext     = panel.querySelector('[data-mplayer-next]');
    var mpShuffle  = panel.querySelector('[data-mplayer-shuffle]');
    var mpRepeat   = panel.querySelector('[data-mplayer-repeat]');
    var mpHeart    = panel.querySelector('[data-mplayer-heart]');
    var mpQueue    = panel.querySelector('[data-mplayer-queue-list]');
    var mpQueueBtn = panel.querySelector('[data-mplayer-queue-btn]');
    var mpMore     = panel.querySelector('.mplayer__more');
    var menu       = panel.querySelector('[data-mplayer-menu]');
    var closeBtn   = document.getElementById('mobilePlayerClose');
    var content    = panel.querySelector('.mplayer__content');

    var seeking = false;

    /* ── Open / Close ────────────────────────────────────────────── */

    function openPlayer() {
        panel.removeAttribute('aria-hidden');
        panel.classList.add('mobile-player--open');
        document.body.classList.add('mplayer-open');
        syncAll();
        if (closeBtn) { closeBtn.focus(); }
    }

    function closePlayer() {
        panel.setAttribute('aria-hidden', 'true');
        panel.classList.remove('mobile-player--open');
        document.body.classList.remove('mplayer-open');
    }

    var miniNow = mainRoot.querySelector('.player__now');
    if (miniNow) { miniNow.addEventListener('click', openPlayer); }
    if (closeBtn) { closeBtn.addEventListener('click', closePlayer); }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !panel.classList.contains('mobile-player--open')) { return; }
        if (menu && !menu.hidden) { closeMenu(); }
        else                      { closePlayer(); }
    });

    /* ── Interactive swipe-down to dismiss ───────────────────────── *
     * The sheet follows the finger (Spotify-style). The gesture only
     * starts when the touch begins outside a range input and the
     * content is scrolled to the top.                                */

    var drag = { active: false, startY: 0, lastY: 0, lastT: 0, velocity: 0 };

    panel.addEventListener('touchstart', function (e) {
        if (e.target.closest('input[type="range"]')) { return; }
        if (e.target.closest('[data-mplayer-menu]')) { return; }
        if (content && content.scrollTop > 4) { return; }
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

    /* ── Helpers ─────────────────────────────────────────────────── */

    function fmt(total) {
        var s = Math.max(0, Math.floor(total || 0));
        var m = Math.floor(s / 60);
        var r = s % 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function currentTrack() {
        return api.getPlaylist()[api.getCurrentIndex()] || null;
    }

    /* ── Sync functions ──────────────────────────────────────────── */

    function syncTrackInfo() {
        var track = currentTrack();
        if (mpTitle)  { mpTitle.textContent  = track ? track.title  : 'Трек не выбран'; }
        if (mpArtist) { mpArtist.textContent = track ? track.artist : '—'; }

        var url = track && track.coverUrl ? track.coverUrl : '';
        if (mpCover) { mpCover.style.backgroundImage = url ? 'url("' + url + '")' : ''; }
        if (mpBg)    { mpBg.style.backgroundImage    = url ? 'url("' + url + '")' : ''; }
    }

    function syncProgress() {
        var dur = isFinite(audio.duration)    ? audio.duration    : 0;
        var cur = isFinite(audio.currentTime) ? audio.currentTime : 0;
        var pct = dur > 0 ? Math.min(100, (cur / dur) * 100) : 0;

        if (!seeking) {
            if (mpFill) { mpFill.style.width = pct + '%';   }
            if (mpSeek) { mpSeek.value       = String(pct); }
        }
        if (mpCurrent)  { mpCurrent.textContent  = fmt(cur); }
        if (mpDuration) { mpDuration.textContent = fmt(dur); }
    }

    function syncPlayState() {
        panel.classList.toggle('mobile-player--playing', mainRoot.hasAttribute('data-playing'));
    }

    function syncModes(detail) {
        if (mpShuffle) { mpShuffle.classList.toggle('is-active', detail.shuffle === true); }
        if (mpRepeat) {
            mpRepeat.classList.toggle('is-active',   detail.repeat === 'all' || detail.repeat === 'one');
            mpRepeat.classList.toggle('is-repeat-one', detail.repeat === 'one');
        }
    }

    function syncHeart() {
        if (!mpHeart) { return; }
        if (!api.isAuth()) { mpHeart.hidden = true; return; }
        var track = currentTrack();
        mpHeart.hidden = !track;
        if (!track) { return; }
        var liked = api.isLiked(track.id);
        mpHeart.classList.toggle('is-liked', liked);
        mpHeart.setAttribute('aria-label', liked ? 'Убрать из избранного' : 'В избранное');
        var svg = mpHeart.querySelector('svg');
        if (svg) { svg.setAttribute('fill', liked ? 'currentColor' : 'none'); }
    }

    /* ── Queue: «Далее в очереди» ────────────────────────────────── */

    function syncQueue() {
        if (!mpQueue) { return; }
        var playlist = api.getPlaylist();
        var cur      = api.getCurrentIndex();

        mpQueue.innerHTML = '';

        if (playlist.length <= 1) {
            var empty = document.createElement('p');
            empty.className = 'mplayer__queue-empty';
            empty.textContent = 'Очередь пуста';
            mpQueue.appendChild(empty);
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
            mpQueue.appendChild(item);
        }
    }

    function syncAll() {
        syncTrackInfo();
        syncProgress();
        syncPlayState();
        syncHeart();
        syncQueue();
    }

    /* ── Controls → player.js ────────────────────────────────────── */

    if (mpToggle) {
        mpToggle.addEventListener('click', function () {
            var mainToggle = mainRoot.querySelector('[data-player-toggle]');
            if (mainToggle) { mainToggle.click(); }
        });
    }
    if (mpPrev) {
        mpPrev.addEventListener('click', function () {
            var b = mainRoot.querySelector('[data-player-prev]');
            if (b) { b.click(); }
        });
    }
    if (mpNext) {
        mpNext.addEventListener('click', function () {
            var b = mainRoot.querySelector('[data-player-next]');
            if (b) { b.click(); }
        });
    }
    if (mpShuffle) {
        mpShuffle.addEventListener('click', function () {
            var b = mainRoot.querySelector('.player__btn--shuffle');
            if (b) { b.click(); }
        });
    }
    if (mpRepeat) {
        mpRepeat.addEventListener('click', function () {
            var b = mainRoot.querySelector('.player__btn--repeat');
            if (b) { b.click(); }
        });
    }
    if (mpHeart) {
        mpHeart.addEventListener('click', function () { api.toggleLike(); });
    }

    /* Seek: preview while dragging, commit on release */
    if (mpSeek) {
        mpSeek.addEventListener('input', function () {
            seeking = true;
            if (mpFill) { mpFill.style.width = mpSeek.value + '%'; }
            var dur = isFinite(audio.duration) ? audio.duration : 0;
            if (mpCurrent && dur > 0) {
                mpCurrent.textContent = fmt((Number(mpSeek.value) / 100) * dur);
            }
        });
        mpSeek.addEventListener('change', function () {
            var dur = isFinite(audio.duration) ? audio.duration : 0;
            if (dur > 0) { audio.currentTime = (Number(mpSeek.value) / 100) * dur; }
            seeking = false;
        });
    }

    /* Queue button — toggles between the queue section and the main view */
    if (mpQueueBtn && content) {
        var queueSection = panel.querySelector('.mplayer__queue');

        /* The queue counts as "shown" once the user is past half of the
           available scroll range. A short queue can never reach the top
           of the screen (scroll clamps), so absolute offsets don't work. */
        function queueShown() {
            var maxScroll = content.scrollHeight - content.clientHeight;
            return maxScroll > 0 && content.scrollTop >= maxScroll * 0.5;
        }

        mpQueueBtn.addEventListener('click', function () {
            if (!queueSection) { return; }
            content.scrollTo({
                top:      queueShown() ? 0 : queueSection.offsetTop,
                behavior: 'smooth'
            });
        });

        /* Keep the button state in sync however the user got there */
        content.addEventListener('scroll', function () {
            mpQueueBtn.classList.toggle('is-active', queueShown());
        }, { passive: true });
    }

    /* ── Three-dots menu: add to playlist · go to author · share ── */

    var menuTrackEl  = menu ? menu.querySelector('[data-mplayer-menu-track]') : null;
    var menuPlaylists = menu ? menu.querySelector('[data-mplayer-menu-playlists]') : null;

    function openMenu() {
        if (!menu) { return; }
        var track = currentTrack();
        if (!track) { return; }
        if (menuTrackEl) { menuTrackEl.textContent = track.title + ' — ' + track.artist; }
        if (menuPlaylists) { menuPlaylists.hidden = true; menuPlaylists.innerHTML = ''; }

        /* Hide actions that can't work for this track / user */
        var plBtn = menu.querySelector('[data-mplayer-action="playlist"]');
        if (plBtn) { plBtn.hidden = !api.isAuth() || api.getUserPlaylists().length === 0; }
        var auBtn = menu.querySelector('[data-mplayer-action="author"]');
        if (auBtn) { auBtn.hidden = !(Number(track.authorId) > 0); }

        menu.hidden = false;
        /* Separate task so the transition runs after `hidden` is flushed */
        setTimeout(function () { menu.classList.add('mplayer__menu--open'); }, 10);
    }

    function closeMenu() {
        if (!menu) { return; }
        menu.classList.remove('mplayer__menu--open');
        setTimeout(function () { menu.hidden = true; }, 200);
    }

    function showPlaylistChoices() {
        if (!menuPlaylists) { return; }
        menuPlaylists.innerHTML = '';
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
            menuPlaylists.appendChild(b);
        });
        menuPlaylists.hidden = false;
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

    if (mpMore && menu) {
        mpMore.addEventListener('click', openMenu);
        menu.addEventListener('click', function (e) {
            if (e.target.closest('[data-mplayer-menu-close]')) { closeMenu(); return; }
            var action = e.target.closest('[data-mplayer-action]');
            if (!action) { return; }
            switch (action.getAttribute('data-mplayer-action')) {
                case 'playlist': showPlaylistChoices(); break;
                case 'author':
                    var track = currentTrack();
                    if (track && Number(track.authorId) > 0) {
                        location.href = '/authors/' + Number(track.authorId);
                    }
                    break;
                case 'share': shareTrack(); break;
            }
        });
    }

    /* ── Subscriptions ───────────────────────────────────────────── */

    audio.addEventListener('play',           syncPlayState);
    audio.addEventListener('pause',          syncPlayState);
    audio.addEventListener('ended',          syncPlayState);
    audio.addEventListener('timeupdate',     syncProgress);
    audio.addEventListener('loadedmetadata', syncProgress);

    mainRoot.addEventListener('rs:trackchange', function () {
        syncTrackInfo();
        syncHeart();
        syncQueue();
    });
    mainRoot.addEventListener('rs:modechange', function (e) { syncModes(e.detail); });
    mainRoot.addEventListener('rs:likechange', function () { syncHeart(); });

    /* Initial sync (player.js has already run) */
    syncAll();

}());
