/* Richsound player — single source of truth for playback state.

   Turbo Drive aware: the page <body> is swapped on navigation while the
   <audio data-turbo-permanent> node and this JS context survive. The first
   execution boots the core (state + audio events, bound once); Turbo
   re-executes body scripts on every visit, so subsequent executions only
   call _rebind(): re-query the fresh DOM, re-attach handlers, repaint.

   Queue semantics (Spotify-like): the active queue lives in memory and
   survives navigation. Each page's window.PLAYER_CONFIG is that page's
   context playlist — clicking a track on a page replaces the queue with
   the page context and plays the clicked index.

   Public API (window.RichsoundPlayer):
     getPlaylist()      → active queue
     getCurrentIndex()  → int
     play(index)        → play track by queue index
     setQueue(tracks)   → replace the queue
     toggleLike()       → like/unlike the current track (auth only)
     isLiked(trackId)   → bool
     getUserPlaylists() → current page's user playlists
     addToPlaylist(id)  → add current track to a playlist

   Events (dispatched on document, bubbling not needed):
     rs:trackchange  detail: { index, track }
     rs:modechange   detail: { shuffle, repeat }   repeat: 'off'|'all'|'one'
     rs:likechange   detail: { trackId, liked, count } */
(function () {
    'use strict';

    if (window.RichsoundPlayer) {
        window.RichsoundPlayer._rebind();
        return;
    }

    var audio = document.querySelector('[data-player-audio]');
    if (!audio) { return; }

    /* ── persistent state (survives Turbo visits) ── */

    var queue          = [];   /* active queue */
    var pageContext    = [];   /* current page's playlist */
    var currentIndex   = -1;
    var listenTracked  = false;
    var shuffleMode    = false;
    var repeatMode     = 'off'; /* 'off' | 'all' | 'one' */
    var shuffleHistory = [];
    var seeking        = false;
    var likedIds       = {};
    var csrfToken      = '';
    var isAuth         = false;
    var userPlaylists  = [];
    var queuePanel     = null;  /* desktop queue popup (lives in body) */
    var queueOpen      = false;
    var coverModal     = null;  /* desktop cover-art preview (lives in body) */

    /* fresh DOM refs, refilled by rebind() on every page visit */
    var ui = {};

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent(name, { detail: detail }));
    }

    /* ── localStorage ────────────────────────── */

    var LS_KEY = 'rs_player_v1';

    function loadSavedState() {
        try {
            return JSON.parse(localStorage.getItem(LS_KEY) || '{}');
        } catch (e) { return {}; }
    }

    function saveState() {
        try {
            localStorage.setItem(LS_KEY, JSON.stringify({
                index:   currentIndex,
                volume:  audio.volume,
                shuffle: shuffleMode,
                repeat:  repeatMode
            }));
        } catch (e) {}
    }

    /* ── helpers ─────────────────────────────── */

    function fmt(totalSeconds) {
        var s = Math.max(0, Math.floor(totalSeconds || 0));
        var m = Math.floor(s / 60);
        var r = s % 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    function setSliderFill(sliderEl, pct) {
        if (sliderEl) { sliderEl.style.setProperty('--fill', pct + '%'); }
    }

    function coverStyle(url) {
        if (!url) { return ''; }
        return 'linear-gradient(180deg,rgba(0,0,0,.28),rgba(0,0,0,.18)),url(\'' + url + '\')';
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Cover-art preview (desktop only) ────────
       Click the player thumbnail to blow the artwork up in a lightbox. */

    function onCoverKey(e) {
        if (e.key === 'Escape') { closeCoverPreview(); }
    }

    function closeCoverPreview() {
        if (!coverModal) { return; }
        document.removeEventListener('keydown', onCoverKey);
        coverModal.remove();
        coverModal = null;
    }

    function openCoverPreview() {
        /* Desktop only — the mobile mini player has its own full-screen view. */
        if (!window.matchMedia('(min-width: 981px)').matches) { return; }
        var track = queue[currentIndex];
        if (!track || !track.coverUrl) { return; }

        closeCoverPreview();

        coverModal = document.createElement('div');
        coverModal.className = 'cover-preview';
        coverModal.innerHTML =
            '<div class="cover-preview__backdrop" data-cover-close></div>' +
            '<figure class="cover-preview__box">' +
                '<img class="cover-preview__img" src="' + escHtml(track.coverUrl) + '" alt="">' +
                '<figcaption class="cover-preview__caption">' +
                    '<span class="cover-preview__title">' + escHtml(track.title || '') + '</span>' +
                    '<span class="cover-preview__artist">' + escHtml(track.artist || '') + '</span>' +
                '</figcaption>' +
                '<button class="cover-preview__close" type="button" data-cover-close aria-label="Закрыть">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                '</button>' +
            '</figure>';

        coverModal.addEventListener('click', function (e) {
            if (e.target.closest('[data-cover-close]')) { closeCoverPreview(); }
        });

        document.body.appendChild(coverModal);
        requestAnimationFrame(function () {
            if (coverModal) { coverModal.classList.add('cover-preview--open'); }
        });
        document.addEventListener('keydown', onCoverKey);
    }

    /* ── painting ────────────────────────────── */

    function updateProgress() {
        var dur = isFinite(audio.duration) ? audio.duration : 0;
        var cur = isFinite(audio.currentTime) ? audio.currentTime : 0;
        var pct = dur > 0 ? Math.min(100, (cur / dur) * 100) : 0;

        /* Don't fight the user's finger while they drag the slider */
        if (!seeking) {
            if (ui.progressInput) { ui.progressInput.value = String(pct); }
            setSliderFill(ui.progressSlider, pct);
        }
        if (ui.currentTimeEl) { ui.currentTimeEl.textContent = fmt(cur); }
        if (ui.durationEl) {
            ui.durationEl.textContent = fmt(dur || (queue[currentIndex] ? queue[currentIndex].duration : 0));
        }
        /* Thin progress line on the mobile mini player */
        if (ui.root) { ui.root.style.setProperty('--mini-progress', pct + '%'); }
    }

    function updateHeart() {
        if (!ui.heartButton) { return; }
        if (!isAuth) { ui.heartButton.hidden = true; return; }
        var track = queue[currentIndex];
        ui.heartButton.hidden = !track;
        if (!track) { return; }
        var liked = !!likedIds[Number(track.id)];
        ui.heartButton.classList.toggle('player__btn--heart-active', liked);
        ui.heartButton.setAttribute('aria-label', liked ? 'Убрать из избранного' : 'В избранное');
        var svg = ui.heartButton.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill', liked ? 'currentColor' : 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('stroke-width', '2');
        }
    }

    function updateModeButtons() {
        if (ui.shuffleButton) { ui.shuffleButton.classList.toggle('player__btn--active', shuffleMode); }
        if (ui.repeatButton) {
            ui.repeatButton.classList.toggle('player__btn--active', repeatMode !== 'off');
            ui.repeatButton.classList.toggle('player__btn--repeat-one', repeatMode === 'one');
        }
        emit('rs:modechange', { shuffle: shuffleMode, repeat: repeatMode });
    }

    function updateUI() {
        var track = queue[currentIndex];
        if (!track) {
            if (ui.titleEl)        { ui.titleEl.textContent  = 'Трек не выбран'; }
            if (ui.artistEl)       { ui.artistEl.textContent = 'Выбери трек'; }
            if (ui.coverEl)        { ui.coverEl.style.backgroundImage = ''; }
            if (ui.progressInput)  { ui.progressInput.value = '0'; }
            setSliderFill(ui.progressSlider, 0);
            updateHeart();
            return;
        }
        if (ui.titleEl)  { ui.titleEl.textContent  = track.title; }
        if (ui.artistEl) { ui.artistEl.textContent = track.artist; }
        if (ui.coverEl)  { ui.coverEl.style.backgroundImage = coverStyle(track.coverUrl); }
        updateHeart();
        updateProgress();
    }

    function setButtonsDisabled(disabled) {
        [ui.toggleButton, ui.prevButton, ui.nextButton].forEach(function (btn) {
            if (!btn) { return; }
            if (disabled) { btn.setAttribute('disabled', 'disabled'); }
            else          { btn.removeAttribute('disabled'); }
        });
    }

    /* Repaint the whole (fresh) player bar from in-memory state */
    function paint() {
        updateUI();
        updateModeButtons();
        setButtonsDisabled(queue.length === 0);
        if (ui.volumeInput) { ui.volumeInput.value = String(audio.volume); }
        setSliderFill(ui.volumeSlider, audio.volume * 100);
        if (ui.root) {
            if (!audio.paused && audio.src) { ui.root.setAttribute('data-playing', '1'); }
            else                            { ui.root.removeAttribute('data-playing'); }
            ui.root.classList.remove('player--buffering');
        }
    }

    /* ── Media Session (lock screen controls) ── */

    function updateMediaSession() {
        if (!('mediaSession' in navigator)) { return; }
        var track = queue[currentIndex];
        if (!track) { return; }
        try {
            navigator.mediaSession.metadata = new MediaMetadata({
                title:  track.title  || '',
                artist: track.artist || '',
                album:  track.album  || '',
                artwork: track.coverUrl
                    ? [{ src: track.coverUrl, sizes: '512x512' }]
                    : []
            });
        } catch (e) {}
    }

    function setupMediaSessionHandlers() {
        if (!('mediaSession' in navigator)) { return; }
        var ms = navigator.mediaSession;
        try {
            ms.setActionHandler('play',  function () { audio.play().catch(function () {}); });
            ms.setActionHandler('pause', function () { audio.pause(); });
            ms.setActionHandler('previoustrack', goPrev);
            ms.setActionHandler('nexttrack',     function () { goNext(true); });
            ms.setActionHandler('seekto', function (d) {
                if (typeof d.seekTime === 'number') { audio.currentTime = d.seekTime; }
            });
        } catch (e) {}
    }

    /* ── listen tracking ─────────────────────── */

    function sendListen(isCompleted) {
        var track = queue[currentIndex];
        if (!track || listenTracked || audio.currentTime < 5) { return; }
        listenTracked = true;
        var body = new URLSearchParams({
            track_id:         String(track.id),
            listened_seconds: String(Math.floor(audio.currentTime || 0)),
            is_completed:     isCompleted ? '1' : '0'
        });
        fetch('/player/listens', {
            method:    'POST',
            headers:   { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
            body:      body,
            keepalive: true
        }).catch(function () {});
    }

    /* ── playback ────────────────────────────── */

    function randomIndexExcluding(exclude) {
        if (queue.length <= 1) { return 0; }
        var idx;
        do { idx = Math.floor(Math.random() * queue.length); } while (idx === exclude);
        return idx;
    }

    function nextShuffleIndex() {
        var idx = randomIndexExcluding(currentIndex);
        shuffleHistory.push(currentIndex);
        if (shuffleHistory.length > 50) { shuffleHistory.shift(); }
        return idx;
    }

    function loadTrack(index, autoplay) {
        if (!queue[index]) { return; }
        currentIndex  = index;
        listenTracked = false;
        audio.src     = queue[index].audioUrl || '';
        audio.load();
        updateUI();
        updateMediaSession();
        saveState();
        if (queueOpen) { renderQueuePanel(); }
        emit('rs:trackchange', { index: index, track: queue[index] });
        if (autoplay && audio.src) {
            audio.play().catch(function () {});
        }
    }

    function setQueue(tracks) {
        queue          = Array.isArray(tracks) ? tracks : [];
        shuffleHistory = [];
        setButtonsDisabled(queue.length === 0);
        if (queueOpen) { renderQueuePanel(); }
    }

    function togglePlayback() {
        if (!queue[currentIndex]) { return; }
        if (!audio.src)    { loadTrack(currentIndex, true); return; }
        if (audio.paused)  { audio.play().catch(function () {}); }
        else               { audio.pause(); }
    }

    function goPrev() {
        if (queue.length === 0) { return; }
        /* Spotify-style: a few seconds in, prev restarts the track */
        if (audio.currentTime > 3 && audio.src) {
            audio.currentTime = 0;
            return;
        }
        if (shuffleMode && shuffleHistory.length > 0) {
            loadTrack(shuffleHistory.pop(), true);
        } else {
            loadTrack((currentIndex - 1 + queue.length) % queue.length, true);
        }
    }

    /* manual=true — user pressed the button: always wraps around.
       manual=false — auto-advance on track end: respects repeatMode. */
    function goNext(manual) {
        if (queue.length === 0) { return; }
        var atEnd = currentIndex >= queue.length - 1;
        if (!manual && repeatMode === 'off' && atEnd) {
            /* End of queue: stay on the last track, paused at the start */
            audio.currentTime = 0;
            updateProgress();
            return;
        }
        var nextIdx = shuffleMode
            ? nextShuffleIndex()
            : (currentIndex + 1) % queue.length;
        loadTrack(nextIdx, true);
    }

    /* ── likes ───────────────────────────────── */

    function setLiked(trackId, liked, count) {
        trackId = Number(trackId);
        if (liked) { likedIds[trackId] = true; }
        else       { delete likedIds[trackId]; }
        updateHeart();
        emit('rs:likechange', { trackId: trackId, liked: liked, count: count });
    }

    /* Reflect player-side like state onto the track cards on the page */
    function syncCardsLike(trackId, liked, count) {
        document.querySelectorAll('.js-like-btn[data-track-id="' + trackId + '"]').forEach(function (btn) {
            btn.classList.toggle('track-actions__like--active', liked);
            btn.setAttribute('aria-label', liked ? 'Убрать лайк' : 'Поставить лайк');
            var icon    = btn.querySelector('.js-like-icon');
            var countEl = btn.querySelector('.js-like-count');
            if (icon)                            { icon.setAttribute('fill', liked ? 'currentColor' : 'none'); }
            if (countEl && count !== undefined)  { countEl.textContent = count; }
        });
    }

    function toggleLike() {
        var track = queue[currentIndex];
        if (!track || !isAuth) { return; }
        var trackId = Number(track.id);
        fetch('/tracks/like', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
            body:    new URLSearchParams({ track_id: String(trackId) })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) { return; }
            setLiked(trackId, !!data.liked, data.count);
            syncCardsLike(trackId, !!data.liked, data.count);
        })
        .catch(function () {});
    }

    function addToPlaylist(playlistId) {
        var track = queue[currentIndex];
        if (!track || !isAuth) { return Promise.resolve(false); }
        return fetch('/playlists/tracks/add', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
            body:    new URLSearchParams({ playlist_id: String(playlistId), track_id: String(track.id) })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { return data.added === true; })
        .catch(function () { return false; });
    }

    /* ── Desktop queue popup ─────────────────── */

    function buildQueuePanel() {
        /* Turbo swaps <body>, so a panel from the previous page is gone —
           detect that and rebuild. */
        if (queuePanel && document.body.contains(queuePanel)) { return; }
        queuePanel = document.createElement('div');
        queuePanel.className = 'player-queue';
        queuePanel.innerHTML = '<div class="player-queue__header"><span>Очередь</span><button type="button" class="player-queue__close" style="background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;font-size:1.1rem;padding:0;" aria-label="Закрыть">✕</button></div><div class="player-queue__list"></div>';
        document.body.appendChild(queuePanel);
        queuePanel.querySelector('.player-queue__close').addEventListener('click', function () { toggleQueuePanel(false); });
    }

    function renderQueuePanel() {
        if (!queuePanel) { return; }
        var listEl = queuePanel.querySelector('.player-queue__list');
        if (!listEl) { return; }

        if (queue.length === 0) {
            listEl.innerHTML = '<p style="padding:16px;color:rgba(255,255,255,.3);font-size:.8rem;text-align:center;">Нет треков</p>';
            return;
        }

        listEl.innerHTML = '';
        queue.forEach(function (track, idx) {
            var item = document.createElement('div');
            item.className = 'player-queue__item' + (idx === currentIndex ? ' player-queue__item--active' : '');
            item.setAttribute('data-queue-index', idx);

            var coverHtml = track.coverUrl
                ? '<img class="player-queue__cover" src="' + track.coverUrl + '" alt="" loading="lazy">'
                : '<div class="player-queue__cover-placeholder"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(139,92,246,.5)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg></div>';

            item.innerHTML = coverHtml +
                '<div class="player-queue__meta">' +
                '<div class="player-queue__title">' + escHtml(track.title) + '</div>' +
                '<div class="player-queue__artist">' + escHtml(track.artist) + '</div>' +
                '</div>';

            item.addEventListener('click', function () { loadTrack(idx, true); });
            listEl.appendChild(item);
        });

        var active = listEl.querySelector('.player-queue__item--active');
        if (active) { active.scrollIntoView({ block: 'nearest' }); }
    }

    function toggleQueuePanel(force) {
        queueOpen = typeof force === 'boolean' ? force : !queueOpen;
        if (queueOpen) {
            buildQueuePanel();
            renderQueuePanel();
            queuePanel.classList.add('player-queue--open');
        } else if (queuePanel) {
            queuePanel.classList.remove('player-queue--open');
        }
    }

    /* ── per-page wiring (runs on boot and on every Turbo visit) ── */

    function rebind() {
        var cfg = window.PLAYER_CONFIG || {};
        if (typeof cfg.csrfToken === 'string' && cfg.csrfToken !== '') { csrfToken = cfg.csrfToken; }
        isAuth        = cfg.isAuth === true;
        userPlaylists = Array.isArray(cfg.userPlaylists) ? cfg.userPlaylists : [];
        (Array.isArray(cfg.likedTrackIds) ? cfg.likedTrackIds : []).forEach(function (id) {
            likedIds[Number(id)] = true;
        });
        pageContext = Array.isArray(cfg.playlist) ? cfg.playlist : [];

        /* The body was swapped: previous page's panel and open state are gone */
        if (queuePanel && !document.body.contains(queuePanel)) {
            queuePanel = null;
            queueOpen  = false;
        }
        closeCoverPreview();
        seeking = false;

        var root = document.querySelector('[data-player-root]');
        ui = {
            root:           root,
            titleEl:        root ? root.querySelector('[data-player-title]')           : null,
            artistEl:       root ? root.querySelector('[data-player-artist]')          : null,
            coverEl:        root ? root.querySelector('[data-player-cover]')           : null,
            toggleButton:   root ? root.querySelector('[data-player-toggle]')          : null,
            prevButton:     root ? root.querySelector('[data-player-prev]')            : null,
            nextButton:     root ? root.querySelector('[data-player-next]')            : null,
            currentTimeEl:  root ? root.querySelector('[data-player-current-time]')    : null,
            durationEl:     root ? root.querySelector('[data-player-duration]')        : null,
            progressInput:  root ? root.querySelector('[data-player-progress]')        : null,
            volumeInput:    root ? root.querySelector('[data-player-volume]')          : null,
            progressSlider: root ? root.querySelector('[data-player-slider-progress]') : null,
            volumeSlider:   root ? root.querySelector('[data-player-slider-volume]')   : null,
            heartButton:    root ? root.querySelector('.player__btn--heart')           : null,
            shuffleButton:  root ? root.querySelector('.player__btn--shuffle')         : null,
            repeatButton:   root ? root.querySelector('.player__btn--repeat')          : null,
            queueButton:    root ? root.querySelector('.player__btn--queue')           : null
        };

        /* All elements are fresh after a body swap — no duplicate listeners */

        if (ui.toggleButton) { ui.toggleButton.addEventListener('click', togglePlayback); }
        if (ui.prevButton)   { ui.prevButton.addEventListener('click', goPrev); }
        if (ui.nextButton)   { ui.nextButton.addEventListener('click', function () { goNext(true); }); }
        if (ui.heartButton)  { ui.heartButton.addEventListener('click', toggleLike); }
        if (ui.queueButton)  { ui.queueButton.addEventListener('click', function () { toggleQueuePanel(); }); }

        if (ui.coverEl) {
            ui.coverEl.setAttribute('role', 'button');
            ui.coverEl.setAttribute('tabindex', '0');
            ui.coverEl.setAttribute('aria-label', 'Открыть обложку');
            ui.coverEl.addEventListener('click', openCoverPreview);
            ui.coverEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openCoverPreview(); }
            });
        }

        if (ui.shuffleButton) {
            ui.shuffleButton.removeAttribute('tabindex');
            ui.shuffleButton.addEventListener('click', function () {
                shuffleMode = !shuffleMode;
                if (shuffleMode) { shuffleHistory = []; }
                updateModeButtons();
                saveState();
            });
        }

        if (ui.repeatButton) {
            ui.repeatButton.removeAttribute('tabindex');
            ui.repeatButton.addEventListener('click', function () {
                repeatMode = repeatMode === 'off' ? 'all' : (repeatMode === 'all' ? 'one' : 'off');
                updateModeButtons();
                saveState();
            });
        }

        /* Seek: preview while dragging, commit on release */
        if (ui.progressInput) {
            ui.progressInput.addEventListener('input', function () {
                seeking = true;
                setSliderFill(ui.progressSlider, Number(ui.progressInput.value));
                var dur = isFinite(audio.duration) ? audio.duration : 0;
                if (ui.currentTimeEl && dur > 0) {
                    ui.currentTimeEl.textContent = fmt((Number(ui.progressInput.value) / 100) * dur);
                }
            });
            ui.progressInput.addEventListener('change', function () {
                var dur = isFinite(audio.duration) ? audio.duration : 0;
                if (dur > 0) { audio.currentTime = (Number(ui.progressInput.value) / 100) * dur; }
                seeking = false;
            });
        }

        if (ui.volumeInput) {
            ui.volumeInput.addEventListener('input', function () {
                var vol = Number(ui.volumeInput.value);
                audio.volume = vol;
                setSliderFill(ui.volumeSlider, vol * 100);
                saveState();
            });
        }

        /* Track cards: play from this page's context playlist */
        document.querySelectorAll('.js-play-track').forEach(function (el) {
            el.addEventListener('click', function (e) {
                var btn = e.target.closest('button');
                if (btn && !btn.classList.contains('js-play-track') && el !== btn) {
                    e.preventDefault();
                }
                var idx = Number(el.getAttribute('data-track-index'));
                if (isNaN(idx)) { return; }
                if (queue !== pageContext) { setQueue(pageContext); }
                loadTrack(idx, true);
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
            });
        });

        paint();
    }

    /* ── audio events (bound ONCE to the permanent audio node) ── */

    audio.addEventListener('loadedmetadata', updateProgress);

    audio.addEventListener('timeupdate', function () {
        updateProgress();
        var dur = isFinite(audio.duration) ? audio.duration : 0;
        if (!listenTracked && audio.currentTime >= Math.min(15, dur * 0.35 || 15)) {
            sendListen(false);
        }
    });

    audio.addEventListener('play', function () {
        if (ui.root) { ui.root.setAttribute('data-playing', '1'); }
    });

    audio.addEventListener('pause', function () {
        if (ui.root) { ui.root.removeAttribute('data-playing'); }
    });

    /* Buffering indicator */
    audio.addEventListener('waiting', function () {
        if (ui.root) { ui.root.classList.add('player--buffering'); }
    });
    ['canplay', 'playing', 'pause'].forEach(function (ev) {
        audio.addEventListener(ev, function () {
            if (ui.root) { ui.root.classList.remove('player--buffering'); }
        });
    });

    audio.addEventListener('ended', function () {
        sendListen(true);
        if (ui.root) { ui.root.removeAttribute('data-playing'); }
        if (repeatMode === 'one') {
            audio.currentTime = 0;
            audio.play().catch(function () {});
        } else if (queue.length > 1 || repeatMode === 'all') {
            goNext(false);
        }
    });

    window.addEventListener('beforeunload', function () { sendListen(false); });

    /* Track cards have their own like handlers (inline in views) — observe
       their clicks and pick up the new state once their fetch settles. */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-like-btn');
        if (!btn || !btn.dataset.trackId) { return; }
        var trackId = Number(btn.dataset.trackId);
        setTimeout(function () {
            setLiked(trackId, btn.classList.contains('track-actions__like--active'));
        }, 400);
    });

    /* ── public API ──────────────────────────── */

    window.RichsoundPlayer = {
        getPlaylist:      function () { return queue; },
        getCurrentIndex:  function () { return currentIndex; },
        play:             function (index) { loadTrack(index, true); },
        setQueue:         setQueue,
        toggleLike:       toggleLike,
        isLiked:          function (trackId) { return !!likedIds[Number(trackId)]; },
        isAuth:           function () { return isAuth; },
        getUserPlaylists: function () { return userPlaylists; },
        addToPlaylist:    addToPlaylist,
        _rebind:          rebind
    };

    /* ── boot (first execution only) ─────────── */

    function boot() {
        var cfg   = window.PLAYER_CONFIG || {};
        var saved = loadSavedState();

        /* volume */
        var vol = (typeof saved.volume === 'number' && !isNaN(saved.volume)) ? saved.volume : 0.8;
        audio.volume = vol;

        /* modes (migration: older builds stored repeat as boolean = repeat-one) */
        shuffleMode = saved.shuffle === true;
        if (saved.repeat === true)                                  { repeatMode = 'one'; }
        else if (saved.repeat === 'all' || saved.repeat === 'one')  { repeatMode = saved.repeat; }
        else                                                        { repeatMode = 'off'; }

        /* cold start: the page context becomes the initial queue */
        function finishBoot() {
            queue = pageContext;
            var initialIndex = typeof cfg.initialIndex === 'number' ? cfg.initialIndex : 0;
            if (typeof saved.index === 'number' && queue[saved.index]) {
                currentIndex = saved.index;
            } else if (queue.length > 0) {
                currentIndex = Math.max(0, Math.min(initialIndex, queue.length - 1));
            }
            setupMediaSessionHandlers();
            paint();
            if (queue[currentIndex]) {
                updateMediaSession();
                emit('rs:trackchange', { index: currentIndex, track: queue[currentIndex] });
            }
        }

        rebind(); /* fills pageContext, csrf, likedIds and wires the page */

        if (pageContext.length === 0 && typeof cfg.fetchUrl === 'string') {
            fetch(cfg.fetchUrl)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    pageContext = Array.isArray(data) ? data : [];
                    finishBoot();
                })
                .catch(function () { finishBoot(); });
        } else {
            finishBoot();
        }
    }

    boot();
}());
