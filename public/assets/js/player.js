/* Richsound player — reads window.PLAYER_CONFIG, supports API playlist fetch + localStorage state */
(function () {
    'use strict';

    var cfg          = window.PLAYER_CONFIG || {};
    var playlist     = Array.isArray(cfg.playlist) ? cfg.playlist : [];
    var initialIndex = typeof cfg.initialIndex === 'number' ? cfg.initialIndex : 0;
    var fetchUrl     = typeof cfg.fetchUrl === 'string' ? cfg.fetchUrl : null;
    var csrfToken    = typeof cfg.csrfToken === 'string' ? cfg.csrfToken : '';

    var audio      = document.querySelector('[data-player-audio]');
    var playerRoot = document.querySelector('[data-player-root]');

    if (!audio || !playerRoot) { return; }

    var titleEl      = playerRoot.querySelector('[data-player-title]');
    var artistEl     = playerRoot.querySelector('[data-player-artist]');
    var coverEl      = playerRoot.querySelector('[data-player-cover]');
    var toggleButton = playerRoot.querySelector('[data-player-toggle]');
    var prevButton   = playerRoot.querySelector('[data-player-prev]');
    var nextButton   = playerRoot.querySelector('[data-player-next]');
    var currentTimeEl   = playerRoot.querySelector('[data-player-current-time]');
    var durationEl      = playerRoot.querySelector('[data-player-duration]');
    var progressInput   = playerRoot.querySelector('[data-player-progress]');
    var volumeInput     = playerRoot.querySelector('[data-player-volume]');
    var progressSlider  = playerRoot.querySelector('[data-player-slider-progress]');
    var volumeSlider    = playerRoot.querySelector('[data-player-slider-volume]');

    var currentIndex  = -1;
    var listenTracked = false;
    var shuffleMode   = false;
    var repeatMode    = false;
    var shuffleHistory = [];

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

    function setSliderFill(sliderEl, pct) {
        if (sliderEl) { sliderEl.style.setProperty('--fill', pct + '%'); }
    }

    function applyVolume(saved) {
        var vol = (typeof saved.volume === 'number' && !isNaN(saved.volume))
            ? saved.volume
            : Number((volumeInput && volumeInput.value) || 0.8);
        audio.volume = vol;
        if (volumeInput) { volumeInput.value = String(vol); }
        setSliderFill(volumeSlider, vol * 100);
    }

    function applyIndex(saved) {
        if (typeof saved.index === 'number' && playlist[saved.index]) {
            currentIndex = saved.index;
        } else if (playlist.length > 0) {
            currentIndex = Math.max(0, Math.min(initialIndex, playlist.length - 1));
        }
    }

    function applyModes(saved) {
        shuffleMode = saved.shuffle === true;
        repeatMode  = saved.repeat === true;
    }

    function updateModeButtons() {
        var shuffleBtn = playerRoot.querySelector('.player__btn--shuffle');
        var repeatBtn  = playerRoot.querySelector('.player__btn--repeat');
        if (shuffleBtn) { shuffleBtn.classList.toggle('player__btn--active', shuffleMode); }
        if (repeatBtn)  { repeatBtn.classList.toggle('player__btn--active', repeatMode); }
    }

    function randomIndexExcluding(exclude) {
        if (playlist.length <= 1) { return 0; }
        var idx;
        do { idx = Math.floor(Math.random() * playlist.length); } while (idx === exclude);
        return idx;
    }

    function nextShuffleIndex() {
        var idx = randomIndexExcluding(currentIndex);
        shuffleHistory.push(currentIndex);
        if (shuffleHistory.length > 50) { shuffleHistory.shift(); }
        return idx;
    }

    /* ── helpers ─────────────────────────────── */

    function fmt(totalSeconds) {
        var s = Math.max(0, Math.floor(totalSeconds || 0));
        var m = Math.floor(s / 60);
        var r = s % 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    function updateProgress() {
        if (!progressInput || !currentTimeEl || !durationEl) { return; }
        var dur  = isFinite(audio.duration) ? audio.duration : 0;
        var cur  = isFinite(audio.currentTime) ? audio.currentTime : 0;
        var pct  = dur > 0 ? Math.min(100, (cur / dur) * 100) : 0;
        progressInput.value           = String(pct);
        currentTimeEl.textContent     = fmt(cur);
        durationEl.textContent        = fmt(dur || (playlist[currentIndex] ? playlist[currentIndex].duration : 0));
        setSliderFill(progressSlider, pct);
    }

    function coverStyle(url) {
        if (!url) { return ''; }
        return 'linear-gradient(180deg,rgba(0,0,0,.28),rgba(0,0,0,.18)),url(\'' + url + '\')';
    }

    function updateUI() {
        var track = playlist[currentIndex];
        if (!track) {
            if (titleEl)       { titleEl.textContent  = 'Трек не выбран'; }
            if (artistEl)      { artistEl.textContent = 'Выбери трек'; }
            if (coverEl)       { coverEl.style.backgroundImage = ''; }
            if (progressInput) { progressInput.value = '0'; }
            setSliderFill(progressSlider, 0);
            return;
        }
        if (titleEl)  { titleEl.textContent  = track.title; }
        if (artistEl) { artistEl.textContent = track.artist; }
        if (coverEl)  { coverEl.style.backgroundImage = coverStyle(track.coverUrl); }
        updateProgress();
    }

    /* ── listen tracking ─────────────────────── */

    function sendListen(isCompleted) {
        var track = playlist[currentIndex];
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

    function setButtonsDisabled(disabled) {
        [toggleButton, prevButton, nextButton].forEach(function (btn) {
            if (!btn) { return; }
            if (disabled) { btn.setAttribute('disabled', 'disabled'); }
            else          { btn.removeAttribute('disabled'); }
        });
    }

    function loadTrack(index, autoplay) {
        if (!playlist[index]) { return; }
        currentIndex  = index;
        listenTracked = false;
        audio.src     = playlist[index].audioUrl || '';
        audio.load();
        updateUI();
        saveState();
        if (queueOpen) { renderQueue(); }
        if (autoplay && audio.src) {
            audio.play().catch(function () {});
        }
    }

    function togglePlayback() {
        if (!playlist[currentIndex]) { return; }
        if (!audio.src)    { loadTrack(currentIndex, true); return; }
        if (audio.paused)  { audio.play().catch(function () {}); }
        else               { audio.pause(); }
    }

    /* ── UI events ───────────────────────────── */

    document.querySelectorAll('.js-play-track').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (btn && !btn.classList.contains('js-play-track') && el !== btn) {
                e.preventDefault();
            }
            var idx = Number(el.getAttribute('data-track-index'));
            if (!isNaN(idx)) { loadTrack(idx, true); }
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
        });
    });

    if (toggleButton) { toggleButton.addEventListener('click', togglePlayback); }

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            if (playlist.length === 0) { return; }
            if (shuffleMode && shuffleHistory.length > 0) {
                loadTrack(shuffleHistory.pop(), true);
            } else {
                loadTrack((currentIndex - 1 + playlist.length) % playlist.length, true);
            }
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            if (playlist.length === 0) { return; }
            var nextIdx = shuffleMode
                ? nextShuffleIndex()
                : (currentIndex + 1) % playlist.length;
            loadTrack(nextIdx, true);
        });
    }

    /* ── Queue panel ─────────────────────────────── */

    var queuePanel  = null;
    var queueOpen   = false;

    function buildQueuePanel() {
        if (queuePanel) { return; }
        queuePanel = document.createElement('div');
        queuePanel.className = 'player-queue';
        queuePanel.innerHTML = '<div class="player-queue__header"><span>Очередь</span><button type="button" class="player-queue__close" style="background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;font-size:1.1rem;padding:0;" aria-label="Закрыть">✕</button></div><div class="player-queue__list"></div>';
        document.body.appendChild(queuePanel);
        queuePanel.querySelector('.player-queue__close').addEventListener('click', function () { toggleQueue(false); });
    }

    function renderQueue() {
        if (!queuePanel) { return; }
        var listEl = queuePanel.querySelector('.player-queue__list');
        if (!listEl) { return; }

        if (playlist.length === 0) {
            listEl.innerHTML = '<p style="padding:16px;color:rgba(255,255,255,.3);font-size:.8rem;text-align:center;">Нет треков</p>';
            return;
        }

        listEl.innerHTML = '';
        playlist.forEach(function (track, idx) {
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

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function toggleQueue(force) {
        queueOpen = typeof force === 'boolean' ? force : !queueOpen;
        if (queueOpen) {
            buildQueuePanel();
            renderQueue();
            queuePanel.classList.add('player-queue--open');
        } else if (queuePanel) {
            queuePanel.classList.remove('player-queue--open');
        }
    }

    var queueButton = playerRoot.querySelector('.player__btn--queue');
    if (queueButton) {
        queueButton.addEventListener('click', function () { toggleQueue(); });
    }

    var shuffleButton = playerRoot.querySelector('.player__btn--shuffle');
    var repeatButton  = playerRoot.querySelector('.player__btn--repeat');

    if (shuffleButton) {
        shuffleButton.removeAttribute('tabindex');
        shuffleButton.addEventListener('click', function () {
            shuffleMode = !shuffleMode;
            if (shuffleMode) { shuffleHistory = []; }
            updateModeButtons();
            saveState();
        });
    }

    if (repeatButton) {
        repeatButton.removeAttribute('tabindex');
        repeatButton.addEventListener('click', function () {
            repeatMode = !repeatMode;
            updateModeButtons();
            saveState();
        });
    }

    if (progressInput) {
        progressInput.addEventListener('input', function () {
            var dur = isFinite(audio.duration) ? audio.duration : 0;
            if (dur > 0) { audio.currentTime = (Number(progressInput.value) / 100) * dur; }
        });
    }

    if (volumeInput) {
        volumeInput.addEventListener('input', function () {
            var vol = Number(volumeInput.value);
            audio.volume = vol;
            setSliderFill(volumeSlider, vol * 100);
            saveState();
        });
    }

    /* ── audio events ────────────────────────── */

    audio.addEventListener('loadedmetadata', updateProgress);

    audio.addEventListener('timeupdate', function () {
        updateProgress();
        var dur = isFinite(audio.duration) ? audio.duration : 0;
        if (!listenTracked && audio.currentTime >= Math.min(15, dur * 0.35 || 15)) {
            sendListen(false);
        }
    });

    audio.addEventListener('play', function () {
        playerRoot.setAttribute('data-playing', '1');
    });

    audio.addEventListener('pause', function () {
        playerRoot.removeAttribute('data-playing');
    });

    audio.addEventListener('ended', function () {
        sendListen(true);
        playerRoot.removeAttribute('data-playing');
        if (repeatMode) {
            audio.currentTime = 0;
            audio.play().catch(function () {});
        } else if (playlist.length > 1) {
            var nextIdx = shuffleMode
                ? nextShuffleIndex()
                : (currentIndex + 1) % playlist.length;
            loadTrack(nextIdx, true);
        }
    });

    window.addEventListener('beforeunload', function () { sendListen(false); });

    /* ── init ────────────────────────────────── */

    function init() {
        var saved = loadSavedState();
        applyVolume(saved);
        applyIndex(saved);
        applyModes(saved);
        updateUI();
        updateModeButtons();
        setButtonsDisabled(playlist.length === 0);
    }

    if (playlist.length === 0 && fetchUrl) {
        fetch(fetchUrl)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                playlist = Array.isArray(data) ? data : [];
                init();
            })
            .catch(function () { init(); });
    } else {
        init();
    }
}());
