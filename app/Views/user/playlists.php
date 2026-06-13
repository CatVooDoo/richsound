<?php

declare(strict_types=1);

$user           = is_array($user ?? null) ? $user : [];
$playlist       = is_array($playlist ?? null) ? $playlist : [];
$tracks         = is_array($tracks ?? null) ? $tracks : [];
$isOwner        = (bool) ($isOwner ?? false);
$playerPlaylist = is_array($playerPlaylist ?? null) ? $playerPlaylist : [];

$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$formatDuration = static function (mixed $s): string {
    $t = max(0, (int) $s);
    return sprintf('%d:%02d', intdiv($t, 60), $t % 60);
};
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($playlist['title'] ?? 'Плейлист') ?> | Richsound</title>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
</head>
<body class="page">
<div class="dashboard">
    <?php $navActive = 'library'; $brandMeta = 'Плейлист'; require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="dashboard__main">
        <section class="section" style="padding-top:32px;">
            <div style="margin-bottom:20px;">
                <a href="/library" style="color:rgba(255,255,255,.5);text-decoration:none;font-size:.85rem;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Библиотека
                </a>
            </div>

            <div style="display:flex;align-items:flex-start;gap:28px;margin-bottom:32px;flex-wrap:wrap;">
                <div style="width:160px;height:160px;border-radius:20px;background:rgba(139,92,246,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="rgba(139,92,246,.6)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle>
                    </svg>
                </div>
                <div style="flex:1;min-width:200px;">
                    <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.4);margin-bottom:8px;">Плейлист</div>
                    <h1 style="font-size:clamp(1.4rem,4vw,2.2rem);font-weight:700;margin:0 0 8px;"><?= $h($playlist['title'] ?? '') ?></h1>
                    <p style="color:rgba(255,255,255,.5);font-size:.88rem;margin:0 0 16px;">
                        <?= $h($playlist['owner_name'] ?? '') ?> ·
                        <?= count($tracks) ?> <?php
                            $n = count($tracks);
                            echo $n === 1 ? 'трек' : ($n < 5 ? 'трека' : 'треков');
                        ?> ·
                        <span style="color:<?= (bool) $playlist['is_public'] ? 'rgba(34,211,238,.7)' : 'rgba(255,255,255,.3)' ?>">
                            <?= (bool) $playlist['is_public'] ? 'Публичный' : 'Приватный' ?>
                        </span>
                    </p>
                    <?php if ($playerPlaylist !== []): ?>
                        <button type="button" class="js-play-all-btn" style="display:inline-flex;align-items:center;gap:8px;background:var(--accent);color:#fff;border:none;border-radius:24px;padding:10px 24px;font-size:.9rem;font-weight:600;cursor:pointer;transition:opacity .18s;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.5.87l10.74-6.86a1 1 0 0 0 0-1.74L9.5 4.27A1 1 0 0 0 8 5.14z"></path></svg>
                            Слушать
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section">
            <?php if ($tracks === []): ?>
                <div class="empty-state">В плейлисте пока нет треков.</div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:2px;">
                    <?php foreach ($tracks as $i => $track): ?>
                        <?php $trackId = (int) $track['id']; ?>
                        <div class="liked-item js-play-track"
                             data-track-index="<?= $h($i) ?>"
                             role="button" tabindex="0"
                             style="display:flex;align-items:center;gap:14px;padding:10px 14px;border-radius:14px;cursor:pointer;transition:background .15s;">
                            <span style="width:22px;text-align:center;color:rgba(255,255,255,.3);font-size:.8rem;flex-shrink:0;"><?= $i + 1 ?></span>
                            <?php if (!empty($track['cover_path'])): ?>
                                <img src="<?= $h($track['cover_path']) ?>" alt="" style="width:44px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                            <?php else: ?>
                                <div style="width:44px;height:44px;border-radius:8px;background:rgba(139,92,246,.12);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(139,92,246,.5)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                                </div>
                            <?php endif; ?>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <a href="/tracks/<?= $h($trackId) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;"><?= $h($track['title']) ?></a>
                                </div>
                                <div style="color:rgba(255,255,255,.5);font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <a href="/authors/<?= $h((int)($track['author_id'] ?? 0)) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;"><?= $h($track['author_name'] ?? '') ?></a>
                                    <?php if (!empty($track['album_title'])): ?> · <?= $h($track['album_title']) ?><?php endif; ?>
                                </div>
                            </div>
                            <span style="color:rgba(255,255,255,.35);font-size:.8rem;flex-shrink:0;"><?= $h($formatDuration($track['duration'] ?? 0)) ?></span>
                            <?php if ($isOwner): ?>
                                <button type="button"
                                        class="js-remove-from-playlist"
                                        data-playlist-id="<?= $h($playlist['id']) ?>"
                                        data-track-id="<?= $h($trackId) ?>"
                                        onclick="event.stopPropagation()"
                                        style="background:none;border:none;color:rgba(255,255,255,.3);cursor:pointer;padding:4px;border-radius:6px;flex-shrink:0;transition:color .15s;"
                                        aria-label="Удалить из плейлиста">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="player" data-player-root>
        <div class="player__info">
            <div class="player__cover" data-player-cover></div>
            <div class="player__meta">
                <span class="player__title" data-player-title>Трек не выбран</span>
                <span class="player__artist" data-player-artist>Выбери трек</span>
            </div>
        </div>
        <div class="player__center">
            <div class="player__controls">
                <button class="player__btn player__btn--shuffle" type="button" title="Перемешать">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line></svg>
                </button>
                <button class="player__btn player__btn--prev" type="button" data-player-prev aria-label="Предыдущий трек">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 20 9 12l10-8v16z"></path><line x1="5" y1="19" x2="5" y2="5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line></svg>
                </button>
                <button class="player__btn player__btn--play" type="button" data-player-toggle aria-label="Воспроизвести">
                    <svg class="player__icon-play" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.5.87l10.74-6.86a1 1 0 0 0 0-1.74L9.5 4.27A1 1 0 0 0 8 5.14z"></path></svg>
                    <svg class="player__icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="4" width="4" height="16" rx="1"></rect><rect x="14" y="4" width="4" height="16" rx="1"></rect></svg>
                </button>
                <button class="player__btn player__btn--next" type="button" data-player-next aria-label="Следующий трек">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 4l10 8-10 8V4z"></path><line x1="19" y1="5" x2="19" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line></svg>
                </button>
                <button class="player__btn player__btn--repeat" type="button" title="Повторить">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                </button>
            </div>
            <div class="player__bar">
                <span class="player__time" data-player-current-time>0:00</span>
                <div class="player__slider" data-player-slider-progress>
                    <div class="player__slider-rail"></div>
                    <div class="player__slider-fill"></div>
                    <input class="player__range" type="range" min="0" max="100" value="0" step="0.1" data-player-progress aria-label="Позиция воспроизведения">
                </div>
                <span class="player__time" data-player-duration>0:00</span>
            </div>
        </div>
        <div class="player__side">
            <button class="player__btn" type="button" aria-label="Громкость">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                    <path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                </svg>
            </button>
            <div class="player__slider player__slider--vol" data-player-slider-volume>
                <div class="player__slider-rail"></div>
                <div class="player__slider-fill"></div>
                <input class="player__range" type="range" min="0" max="1" step="0.01" value="0.8" data-player-volume aria-label="Громкость">
            </div>
            <button class="player__btn player__btn--queue" type="button" title="Очередь" aria-label="Очередь">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </button>
        </div>
    </footer>
</div>

<?php require __DIR__ . '/../partials/mobile-player.php'; ?>
<?php require __DIR__ . '/../partials/player-config.php'; ?>
<script>
(function () {
    'use strict';

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* Hover effect on track rows */
    document.querySelectorAll('.liked-item').forEach(function (row) {
        row.addEventListener('mouseenter', function () { row.style.background = 'rgba(255,255,255,.04)'; });
        row.addEventListener('mouseleave', function () { row.style.background = 'transparent'; });
    });

    /* Play-all button */
    var playAllBtn = document.querySelector('.js-play-all-btn');
    if (playAllBtn) {
        playAllBtn.addEventListener('click', function () {
            var first = document.querySelector('.js-play-track[data-track-index="0"]');
            if (first) { first.click(); }
        });
    }

    /* Remove track from playlist */
    document.querySelectorAll('.js-remove-from-playlist').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch('/playlists/tracks/remove', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body:    new URLSearchParams({ playlist_id: btn.dataset.playlistId, track_id: btn.dataset.trackId })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.error && data.removed) {
                    var row = btn.closest('.liked-item');
                    if (row) {
                        row.style.transition = 'opacity .22s';
                        row.style.opacity    = '0';
                        setTimeout(function () { row.remove(); }, 240);
                    }
                }
            })
            .catch(function () {});
        });
    });
}());
</script>
</body>
</html>
