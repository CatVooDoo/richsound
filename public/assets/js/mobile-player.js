/* Richsound — mobile full-screen player
   Syncs with the existing player.js via audio events + MutationObserver.
   Does NOT modify player.js — all state lives there. */
(function () {
    'use strict';

    var panel    = document.getElementById('mobilePlayer');
    var audio    = document.querySelector('[data-player-audio]');
    var mainRoot = document.querySelector('[data-player-root]');

    if (!panel || !audio || !mainRoot) { return; }

    /* ── Mobile player element refs ─────────────────────────────── */
    var mpTitle    = panel.querySelector('[data-mplayer-title]');
    var mpArtist   = panel.querySelector('[data-mplayer-artist]');
    var mpCover    = panel.querySelector('[data-mplayer-cover]');
    var mpBg       = panel.querySelector('[data-mplayer-bg]');
    var mpFill     = panel.querySelector('[data-mplayer-fill]');
    var mpCurrent  = panel.querySelector('[data-mplayer-current]');
    var mpDuration = panel.querySelector('[data-mplayer-duration]');
    var mpSeek     = panel.querySelector('[data-mplayer-seek]');
    var mpVolume   = panel.querySelector('[data-mplayer-volume]');
    var mpVolFill  = panel.querySelector('[data-mplayer-vol-fill]');
    var mpToggle   = panel.querySelector('[data-mplayer-toggle]');
    var mpPrev     = panel.querySelector('[data-mplayer-prev]');
    var mpNext     = panel.querySelector('[data-mplayer-next]');
    var mpShuffle  = panel.querySelector('[data-mplayer-shuffle]');
    var mpRepeat   = panel.querySelector('[data-mplayer-repeat]');
    var mpHeart    = panel.querySelector('[data-mplayer-heart]');
    var closeBtn   = document.getElementById('mobilePlayerClose');

    /* ── Main player element refs (source of truth) ─────────────── */
    var mainTitle   = mainRoot.querySelector('[data-player-title]');
    var mainArtist  = mainRoot.querySelector('[data-player-artist]');
    var mainCover   = mainRoot.querySelector('[data-player-cover]');
    var mainToggle  = mainRoot.querySelector('[data-player-toggle]');
    var mainPrev    = mainRoot.querySelector('[data-player-prev]');
    var mainNext    = mainRoot.querySelector('[data-player-next]');
    var mainVolume  = mainRoot.querySelector('[data-player-volume]');
    var mainShuffle = mainRoot.querySelector('.player__btn--shuffle');
    var mainRepeat  = mainRoot.querySelector('.player__btn--repeat');
    var mainHeart   = mainRoot.querySelector('.player__btn--heart');

    /* ── Open / Close ────────────────────────────────────────────── */

    function openPlayer() {
        panel.removeAttribute('aria-hidden');
        panel.classList.add('mobile-player--open');
        document.body.classList.add('mplayer-open');
        syncAll();
    }

    function closePlayer() {
        panel.setAttribute('aria-hidden', 'true');
        panel.classList.remove('mobile-player--open');
        document.body.classList.remove('mplayer-open');
    }

    var miniNow = mainRoot.querySelector('.player__now');
    if (miniNow) { miniNow.addEventListener('click', openPlayer); }
    if (closeBtn) { closeBtn.addEventListener('click', closePlayer); }

    /* Swipe-down to dismiss — only when content is scrolled to top */
    var touchStartY = 0;
    var content = panel.querySelector('.mplayer__content');
    panel.addEventListener('touchstart', function (e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });
    panel.addEventListener('touchend', function (e) {
        var scrollTop = content ? content.scrollTop : 0;
        var dy = e.changedTouches[0].clientY - touchStartY;
        if (dy > 70 && scrollTop <= 4) { closePlayer(); }
    }, { passive: true });

    /* ── Helpers ─────────────────────────────────────────────────── */

    function fmt(total) {
        var s = Math.max(0, Math.floor(total || 0));
        var m = Math.floor(s / 60);
        var r = s % 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    /* Extract raw URL from background-image like "gradient(...),url('...')" */
    function extractUrl(bgImage) {
        var m = bgImage.match(/url\(['"]?([^'")\s]+)['"]?\)/);
        return m ? m[1] : '';
    }

    /* ── Sync functions ──────────────────────────────────────────── */

    function syncTrackInfo() {
        if (mainTitle  && mpTitle)  { mpTitle.textContent  = mainTitle.textContent;  }
        if (mainArtist && mpArtist) { mpArtist.textContent = mainArtist.textContent; }

        if (mainCover && mpCover) {
            var bg  = mainCover.style.backgroundImage;
            var url = extractUrl(bg);

            /* Cover art in mobile player — plain url() without the dark gradient overlay */
            mpCover.style.backgroundImage = url ? 'url("' + url + '")' : '';

            /* Blurred background layer */
            if (mpBg) {
                mpBg.style.backgroundImage = url ? 'url("' + url + '")' : '';
            }
        }
    }

    function syncProgress() {
        var dur = isFinite(audio.duration)   ? audio.duration   : 0;
        var cur = isFinite(audio.currentTime) ? audio.currentTime : 0;
        var pct = dur > 0 ? Math.min(100, (cur / dur) * 100) : 0;

        if (mpFill)     { mpFill.style.width       = pct + '%';   }
        if (mpSeek)     { mpSeek.value             = String(pct); }
        if (mpCurrent)  { mpCurrent.textContent    = fmt(cur);    }
        if (mpDuration) { mpDuration.textContent   = fmt(dur);    }
    }

    function syncPlayState() {
        var playing = mainRoot.hasAttribute('data-playing');
        panel.classList.toggle('mobile-player--playing', playing);
    }

    function syncVolume() {
        if (!mainVolume) { return; }
        var vol = Number(mainVolume.value);
        if (mpVolume) { mpVolume.value = String(vol); }
        if (mpVolFill) { mpVolFill.style.width = (vol * 100) + '%'; }
    }

    function syncModes() {
        if (mainShuffle && mpShuffle) {
            mpShuffle.classList.toggle('is-active',
                mainShuffle.classList.contains('player__btn--active'));
        }
        if (mainRepeat && mpRepeat) {
            mpRepeat.classList.toggle('is-active',
                mainRepeat.classList.contains('player__btn--active'));
        }
    }

    function syncAll() {
        syncTrackInfo();
        syncProgress();
        syncPlayState();
        syncVolume();
        syncModes();
    }

    /* ── Button delegates → trigger main player buttons ─────────── */

    if (mpToggle && mainToggle) {
        mpToggle.addEventListener('click', function () { mainToggle.click(); });
    }
    if (mpPrev && mainPrev) {
        mpPrev.addEventListener('click', function () { mainPrev.click(); });
    }
    if (mpNext && mainNext) {
        mpNext.addEventListener('click', function () { mainNext.click(); });
    }
    if (mpShuffle && mainShuffle) {
        mpShuffle.addEventListener('click', function () {
            mainShuffle.click();
            /* Small delay so class updates before we read them */
            setTimeout(syncModes, 30);
        });
    }
    if (mpRepeat && mainRepeat) {
        mpRepeat.addEventListener('click', function () {
            mainRepeat.click();
            setTimeout(syncModes, 30);
        });
    }
    if (mpHeart && mainHeart) {
        mpHeart.addEventListener('click', function () { mainHeart.click(); });
    }

    /* Seek scrubber */
    if (mpSeek) {
        mpSeek.addEventListener('input', function () {
            /* Update fill immediately for drag feedback */
            if (mpFill) { mpFill.style.width = mpSeek.value + '%'; }
            var dur = isFinite(audio.duration) ? audio.duration : 0;
            if (dur > 0) { audio.currentTime = (Number(mpSeek.value) / 100) * dur; }
        });
    }

    /* Volume scrubber — propagate back to main player slider */
    if (mpVolume) {
        mpVolume.addEventListener('input', function () {
            var vol = Number(mpVolume.value);
            audio.volume = vol;
            if (mpVolFill) { mpVolFill.style.width = (vol * 100) + '%'; }
            if (mainVolume) {
                mainVolume.value = String(vol);
                mainVolume.dispatchEvent(new Event('input')); /* triggers main fill + saveState */
            }
        });
    }

    /* ── Audio events → update mobile UI ────────────────────────── */

    audio.addEventListener('play',            syncPlayState);
    audio.addEventListener('pause',           syncPlayState);
    audio.addEventListener('ended',           syncPlayState);
    audio.addEventListener('timeupdate',      syncProgress);
    audio.addEventListener('loadedmetadata',  syncProgress);

    /* ── MutationObserver: react to track changes in main player ── */

    var mo = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            var t = m.target;
            /* Text changed in title or artist */
            if (t === mainTitle || t === mainArtist ||
                (t.parentNode === mainTitle) || (t.parentNode === mainArtist)) {
                syncTrackInfo();
            }
            /* Cover style changed */
            if (t === mainCover && m.attributeName === 'style') {
                syncTrackInfo();
            }
            /* data-playing added/removed on player root */
            if (t === mainRoot && m.attributeName === 'data-playing') {
                syncPlayState();
            }
            /* Shuffle/repeat class toggled */
            if ((t === mainShuffle || t === mainRepeat) && m.attributeName === 'class') {
                syncModes();
            }
        });
    });

    mo.observe(mainRoot,   { attributes: true, attributeFilter: ['data-playing'] });
    mo.observe(mainCover,  { attributes: true, attributeFilter: ['style'] });
    mo.observe(mainTitle,  { characterData: true, childList: true, subtree: true });
    mo.observe(mainArtist, { characterData: true, childList: true, subtree: true });
    if (mainShuffle) { mo.observe(mainShuffle, { attributes: true, attributeFilter: ['class'] }); }
    if (mainRepeat)  { mo.observe(mainRepeat,  { attributes: true, attributeFilter: ['class'] }); }

    /* Initial sync after player.js has run */
    syncAll();

}());
