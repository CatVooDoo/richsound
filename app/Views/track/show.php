<?php

declare(strict_types=1);

$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$formatDuration = static function (mixed $s): string {
    $t = max(0, (int) $s);
    return sprintf('%d:%02d', intdiv($t, 60), $t % 60);
};
$normalizeMediaUrl = static function (?string $path): string {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path) === 1 || str_starts_with($path, '/')) {
        return $path;
    }
    return '/' . ltrim($path, '/');
};

$isAuthenticated = $user !== null;
$track           = is_array($track ?? null) ? $track : [];
$userPlaylists   = is_array($userPlaylists ?? null) ? $userPlaylists : [];
$isLiked         = (bool) ($isLiked ?? false);

$trackId      = (int) ($track['id'] ?? 0);
$trackTitle   = (string) ($track['title'] ?? 'Без названия');
$authorId     = (int) ($track['author_id'] ?? 0);
$authorName   = (string) ($track['author_name'] ?? 'Неизвестный исполнитель');
$albumId      = (int) ($track['album_id'] ?? 0);
$albumTitle   = (string) ($track['album_title'] ?? '');
$coverPath    = $normalizeMediaUrl((string) ($track['cover_path'] ?? ''));
$duration     = (int) ($track['duration'] ?? 0);
$playsCount   = (int) ($track['plays_count'] ?? 0);
$likesCount   = (int) ($track['likes_count'] ?? 0);

$hasCover = $coverPath !== '';
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($trackTitle) ?> | Richsound</title>
    <link rel="stylesheet" href="/assets/css/home.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <style>
        .track-page {
            min-height: calc(100vh - 90px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 48px 24px 120px;
            background: #0a0a12;
        }

        .back-link {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 40px;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: rgba(255, 255, 255, 0.9);
        }

        .track-hero {
            display: flex;
            align-items: flex-start;
            gap: 48px;
            width: 100%;
            max-width: 860px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(12px);
        }

        .track-cover {
            flex-shrink: 0;
            width: 400px;
            height: 400px;
            border-radius: 14px;
            background-color: rgba(139, 92, 246, 0.15);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        .track-cover--placeholder {
            background-image: linear-gradient(135deg, rgba(139, 92, 246, 0.4) 0%, rgba(6, 182, 212, 0.25) 60%, rgba(139, 92, 246, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .track-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-top: 8px;
        }

        .track-label {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent, #8b5cf6);
            background: rgba(139, 92, 246, 0.12);
            border: 1px solid rgba(139, 92, 246, 0.25);
            border-radius: 4px;
            padding: 3px 10px;
        }

        .track-title {
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1.15;
            color: #fff;
            margin: 0;
            word-break: break-word;
        }

        .track-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.55);
        }
        .track-meta a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .track-meta a:hover {
            color: var(--accent, #8b5cf6);
        }
        .track-meta__sep {
            color: rgba(255, 255, 255, 0.25);
        }

        .track-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
        }
        .track-stat {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .track-stat__value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
        }
        .track-stat__label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .track-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-play {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(.34,1.56,.64,1), box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        }
        .btn-play:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.6);
        }
        .btn-play:active {
            transform: translateY(0);
            box-shadow: 0 3px 14px rgba(139, 92, 246, 0.4);
        }
        .btn-play:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.55), 0 4px 20px rgba(139, 92, 246, 0.4);
        }

        .track-actions__like {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .track-actions__like:hover {
            background: rgba(139, 92, 246, 0.12);
            border-color: rgba(139, 92, 246, 0.35);
            color: #fff;
        }
        .track-actions__like:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.5);
        }
        .track-actions__like--active {
            color: #f472b6;
            border-color: rgba(244, 114, 182, 0.4);
            background: rgba(244, 114, 182, 0.08);
        }
        .track-actions__like--active:hover {
            background: rgba(244, 114, 182, 0.14);
        }

        .track-actions__playlist-wrap {
            position: relative;
        }
        .track-actions__add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .track-actions__add:hover {
            background: rgba(6, 182, 212, 0.1);
            border-color: rgba(6, 182, 212, 0.3);
            color: #fff;
        }

        .playlist-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            z-index: 120;
            min-width: 200px;
            background: #18182a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            list-style: none;
            margin: 0;
            padding: 6px 0;
            overflow: hidden;
        }
        .playlist-dropdown li {
            margin: 0;
        }
        .playlist-dropdown button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 10px 16px;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .playlist-dropdown button:hover {
            background: rgba(139, 92, 246, 0.12);
            color: #fff;
        }

        .track-auth-hint {
            font-size: 0.8125rem;
            color: rgba(255, 255, 255, 0.35);
            margin: 0;
        }
        .track-auth-hint a {
            color: var(--accent, #8b5cf6);
            text-decoration: none;
            font-weight: 600;
        }
        .track-auth-hint a:hover {
            text-decoration: underline;
        }
        .track-auth-hint a:focus-visible {
            outline: none;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .back-link:focus-visible {
            outline: none;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        @media (max-width: 820px) {
            .track-hero {
                flex-direction: column;
                align-items: center;
                gap: 32px;
                padding: 28px 20px;
            }
            .track-cover {
                width: 100%;
                max-width: 320px;
                height: 320px;
            }
            .track-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body class="page">

<div class="track-page">
    <a href="/" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5"/><path d="m12 5-7 7 7 7"/>
        </svg>
        Главная
    </a>

    <div class="track-hero">

        <?php if ($hasCover): ?>
            <div class="track-cover"
                 style="background-image: url('<?= $h($coverPath) ?>');"
                 role="img"
                 aria-label="Обложка трека <?= $h($trackTitle) ?>"></div>
        <?php else: ?>
            <div class="track-cover track-cover--placeholder" role="img" aria-label="Обложка трека">
                <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity:.3">
                    <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                </svg>
            </div>
        <?php endif; ?>

        <div class="track-info">
            <span class="track-label">Трек</span>

            <h1 class="track-title"><?= $h($trackTitle) ?></h1>

            <div class="track-meta">
                <a href="/authors/<?= $h($authorId) ?>"><?= $h($authorName) ?></a>
                <?php if ($albumId > 0 && $albumTitle !== ''): ?>
                    <span class="track-meta__sep">·</span>
                    <a href="/albums/<?= $h($albumId) ?>"><?= $h($albumTitle) ?></a>
                <?php endif; ?>
            </div>

            <div class="track-stats">
                <div class="track-stat">
                    <span class="track-stat__value"><?= $h($formatDuration($duration)) ?></span>
                    <span class="track-stat__label">Длительность</span>
                </div>
                <div class="track-stat">
                    <span class="track-stat__value"><?= $h(number_format($playsCount, 0, ',', "\u{00A0}")) ?></span>
                    <span class="track-stat__label">Воспроизведений</span>
                </div>
                <div class="track-stat">
                    <span class="track-stat__value"><?= $h(number_format($likesCount, 0, ',', "\u{00A0}")) ?></span>
                    <span class="track-stat__label">Лайков</span>
                </div>
            </div>

            <div class="track-actions" onclick="event.stopPropagation()">
                <button class="btn-play js-play-track"
                        type="button"
                        data-track-index="0"
                        aria-label="Воспроизвести <?= $h($trackTitle) ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Слушать
                </button>

                <?php if ($isAuthenticated): ?>
                    <button type="button"
                            class="track-actions__like js-like-btn<?= $isLiked ? ' track-actions__like--active' : '' ?>"
                            data-track-id="<?= $h($trackId) ?>"
                            aria-label="<?= $isLiked ? 'Убрать лайк' : 'Поставить лайк' ?>">
                        <svg width="15" height="15" viewBox="0 0 24 24"
                             fill="<?= $isLiked ? 'currentColor' : 'none' ?>"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true" class="js-like-icon">
                            <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                        </svg>
                        <span class="js-like-count"><?= $h($likesCount) ?></span>
                    </button>

                    <?php if ($userPlaylists !== []): ?>
                        <div class="track-actions__playlist-wrap">
                            <button type="button"
                                    class="track-actions__add js-playlist-btn"
                                    data-track-id="<?= $h($trackId) ?>"
                                    aria-label="Добавить в плейлист">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 5v14"/><path d="M5 12h14"/>
                                </svg>
                                В плейлист
                            </button>
                            <ul class="playlist-dropdown" hidden>
                                <?php foreach ($userPlaylists as $pl): ?>
                                    <li>
                                        <button type="button"
                                                class="js-add-to-playlist"
                                                data-playlist-id="<?= $h($pl['id']) ?>"
                                                data-track-id="<?= $h($trackId) ?>">
                                            <?= $h($pl['title']) ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="track-auth-hint">
                        <a href="/login" data-turbo="false">Войди</a>, чтобы ставить лайки
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>


<?php require __DIR__ . '/../partials/player-bar.php'; ?>

<?php
$playlist = [[
    'id'       => $trackId,
    'title'    => $trackTitle,
    'artist'   => $authorName,
    'authorId' => (int) ($track['author_id'] ?? 0),
    'album'    => $albumTitle,
    'audioUrl' => '/player/stream?id=' . $trackId,
    'coverUrl' => $coverPath,
    'duration' => $duration,
    'plays'    => $playsCount,
    'likes'    => $likesCount,
]];
require __DIR__ . '/../partials/mobile-player.php';
require __DIR__ . '/../partials/player-config.php';
?>
<script>
(function () {
    'use strict';

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ── Like button ─────────────────────────────── */
    document.querySelectorAll('.js-like-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var trackId = btn.dataset.trackId;
            var body    = new URLSearchParams({ track_id: trackId });

            fetch('/tracks/like', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body:    body
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { return; }
                var icon  = btn.querySelector('.js-like-icon');
                var count = btn.querySelector('.js-like-count');
                btn.classList.toggle('track-actions__like--active', data.liked);
                btn.setAttribute('aria-label', data.liked ? 'Убрать лайк' : 'Поставить лайк');
                if (icon)  { icon.setAttribute('fill', data.liked ? 'currentColor' : 'none'); }
                if (count) { count.textContent = data.count; }
            })
            .catch(function () {});
        });
    });

    /* ── Playlist dropdown ───────────────────────── */
    document.querySelectorAll('.js-playlist-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var wrap     = btn.closest('.track-actions__playlist-wrap');
            var dropdown = wrap && wrap.querySelector('.playlist-dropdown');
            if (!dropdown) { return; }
            var isHidden = dropdown.hidden;
            document.querySelectorAll('.playlist-dropdown').forEach(function (d) { d.hidden = true; });
            dropdown.hidden = !isHidden;
        });
    });

    document.querySelectorAll('.js-add-to-playlist').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var playlistId = btn.dataset.playlistId;
            var trackId    = btn.dataset.trackId;
            var body       = new URLSearchParams({ playlist_id: playlistId, track_id: trackId });

            fetch('/playlists/tracks/add', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body:    body
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var wrap = btn.closest('.track-actions__playlist-wrap');
                if (wrap) {
                    var dropdown = wrap.querySelector('.playlist-dropdown');
                    if (dropdown) { dropdown.hidden = true; }
                }
                if (data.added) {
                    btn.textContent = '✓ ' + btn.textContent.replace(/^✓\s*/, '');
                }
            })
            .catch(function () {});
        });
    });

    if (!window.__rsDropdownDocBound) {
        window.__rsDropdownDocBound = true;
        document.addEventListener('click', function () {
            document.querySelectorAll('.playlist-dropdown').forEach(function (d) { d.hidden = true; });
        });
    }
}());
</script>
</body>
</html>
