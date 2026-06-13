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

$user            = is_array($user ?? null) ? $user : null;
$isAuthenticated = $user !== null;
$album           = is_array($album ?? null) ? $album : [];
$tracks          = is_array($tracks ?? null) ? $tracks : [];
$likedTrackIds   = is_array($likedTrackIds ?? null) ? array_map('intval', $likedTrackIds) : [];
$userPlaylists   = is_array($userPlaylists ?? null) ? $userPlaylists : [];

$albumId      = (int) ($album['id'] ?? 0);
$albumTitle   = (string) ($album['title'] ?? 'Без названия');
$authorId     = (int) ($album['author_id'] ?? 0);
$authorName   = (string) ($album['author_name'] ?? 'Неизвестный исполнитель');
$coverPath    = $normalizeMediaUrl((string) ($album['cover_path'] ?? ''));
$releasedAt   = (string) ($album['released_at'] ?? '');
$tracksCount  = (int) ($album['tracks_count'] ?? count($tracks));
$hasCover     = $coverPath !== '';

/* Build playlist for player — only tracks with a file */
$playlist        = [];
$trackIndexById  = [];

foreach ($tracks as $track) {
    if (!is_array($track)) {
        continue;
    }

    $tid      = (int) ($track['id'] ?? 0);
    $audioUrl = $normalizeMediaUrl((string) ($track['file_path'] ?? ''));

    if ($tid <= 0 || $audioUrl === '' || isset($trackIndexById[$tid])) {
        continue;
    }

    $playlist[] = [
        'id'       => $tid,
        'title'    => (string) ($track['title'] ?? 'Без названия'),
        'artist'   => (string) ($track['author_name'] ?? $authorName),
        'album'    => $albumTitle,
        'audioUrl' => '/player/stream?id=' . $tid,
        'coverUrl' => $normalizeMediaUrl((string) ($track['cover_path'] ?? $coverPath)),
        'duration' => (int) ($track['duration'] ?? 0),
        'plays'    => (int) ($track['plays_count'] ?? 0),
        'likes'    => (int) ($track['likes_count'] ?? 0),
    ];
    $trackIndexById[$tid] = count($playlist) - 1;
}

/* Format release date */
$releasedFormatted = '';
if ($releasedAt !== '') {
    $ts = strtotime($releasedAt);
    if ($ts !== false) {
        $releasedFormatted = date('Y', $ts);
    }
}

$roleLabels = ['listener' => 'Слушатель', 'author' => 'Автор', 'admin' => 'Администратор'];
$userRole   = $isAuthenticated ? ($roleLabels[$user['role'] ?? ''] ?? 'Аккаунт') : 'Гость';
$userName   = $user['name'] ?? null;

// Context for the shared sidebar partial (no nav item matches an album page).
$navActive = '';
$brandMeta = $userRole;
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($albumTitle) ?> | Richsound</title>
    <script src="/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <style>
        /* ── Album hero + tracklist living inside the dashboard shell's
              .main__content. Colours use home.css design tokens so both
              the dark and light themes work without a separate override block. ── */

        .album-hero {
            display: flex;
            align-items: flex-end;
            gap: 36px;
            margin-bottom: 32px;
            background: var(--panel-soft);
            border: 1px solid var(--stroke);
            border-radius: var(--radius-xl, 32px);
            padding: 36px;
        }

        .album-cover {
            flex-shrink: 0;
            width: 300px;
            height: 300px;
            border-radius: 12px;
            background-color: rgba(139, 92, 246, 0.15);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.35);
        }
        .album-cover--placeholder {
            background-image: linear-gradient(135deg, rgba(139, 92, 246, 0.4) 0%, rgba(6, 182, 212, 0.25) 60%, rgba(139, 92, 246, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .album-hero__info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .album-label {
            align-self: flex-start; /* hug content — was stretching full width in the flex column */
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

        .album-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.12;
            color: var(--text);
            margin: 0;
            word-break: break-word;
        }

        .album-hero__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            font-size: 0.9375rem;
            color: var(--muted);
        }
        .album-hero__meta a {
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .album-hero__meta a:hover {
            color: var(--accent, #8b5cf6);
        }
        .album-hero__meta__sep {
            color: var(--muted-soft);
        }

        .album-hero__actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }

        .btn-play-all {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 13px 28px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 0.9375rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(.34,1.56,.64,1), box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        }
        .btn-play-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.6);
        }
        .btn-play-all:active {
            transform: translateY(0);
            box-shadow: 0 3px 14px rgba(139, 92, 246, 0.4);
        }
        .btn-play-all:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.55), 0 4px 20px rgba(139, 92, 246, 0.4);
        }
        .btn-play-all:disabled {
            opacity: 0.4;
            cursor: default;
            box-shadow: none;
            transform: none;
        }

        /* ── Tracklist ───────────────────────────── */
        .tracklist-section {
            background: var(--panel-soft);
            border: 1px solid var(--stroke);
            border-radius: var(--radius-lg, 26px);
            overflow: hidden;
        }

        .tracklist-header {
            display: grid;
            grid-template-columns: 40px 40px 1fr auto auto;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--stroke);
            color: var(--muted-soft);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .tracklist-row {
            display: grid;
            grid-template-columns: 40px 40px 1fr auto auto;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--stroke);
            transition: background 0.15s;
        }
        .tracklist-row:last-child {
            border-bottom: none;
        }
        .tracklist-row:hover {
            background: rgba(139, 92, 246, 0.07);
        }
        .tracklist-row:focus-visible {
            outline: none;
            background: rgba(139, 92, 246, 0.08);
            box-shadow: inset 0 0 0 1px rgba(139, 92, 246, 0.25);
        }
        .tracklist-row:hover .track-row__index {
            display: none;
        }
        .tracklist-row:hover .track-row__play {
            display: flex;
        }

        .track-row__num {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .track-row__index {
            font-size: 0.875rem;
            color: var(--muted-soft);
            font-weight: 500;
        }

        .track-row__play {
            display: none;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: none;
            border: none;
            color: var(--text);
            cursor: pointer;
            border-radius: 50%;
            transition: color 0.15s;
            padding: 0;
        }
        .track-row__play:hover {
            color: var(--accent, #8b5cf6);
        }
        .track-row__play:disabled {
            opacity: 0.3;
            cursor: default;
        }

        .track-row__thumb {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            background-color: rgba(139, 92, 246, 0.12);
            background-size: cover;
            background-position: center;
            flex-shrink: 0;
        }
        .track-row__thumb--placeholder {
            background-image: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.15));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .track-row__info {
            min-width: 0;
        }
        .track-row__title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .track-row__artist {
            font-size: 0.8125rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        .track-row__duration {
            font-size: 0.875rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .track-row__actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .track-row__like {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            background: none;
            border: 1px solid var(--stroke);
            border-radius: 50px;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s;
            white-space: nowrap;
        }
        .track-row__like:hover {
            border-color: rgba(139, 92, 246, 0.35);
            color: var(--text);
            background: rgba(139, 92, 246, 0.08);
        }
        .track-row__like--active {
            color: var(--accent, #8b5cf6);
            border-color: rgba(139, 92, 246, 0.4);
            background: rgba(139, 92, 246, 0.08);
        }

        .track-row__playlist-wrap {
            position: relative;
        }
        .track-row__add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: none;
            border: 1px solid var(--stroke);
            border-radius: 50%;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.18s;
            padding: 0;
        }
        .track-row__add:hover {
            border-color: rgba(6, 182, 212, 0.35);
            color: var(--text);
            background: rgba(6, 182, 212, 0.08);
        }

        .playlist-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 120;
            min-width: 190px;
            background: var(--panel);
            border: 1px solid var(--stroke-strong);
            border-radius: 10px;
            box-shadow: var(--shadow-card);
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
            color: var(--muted);
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .playlist-dropdown button:hover {
            background: rgba(139, 92, 246, 0.12);
            color: var(--text);
        }

        .empty-tracklist {
            padding: 48px 24px;
            text-align: center;
            color: var(--muted-soft);
            font-size: 0.9375rem;
        }

        @media (max-width: 720px) {
            .album-hero {
                flex-direction: column;
                align-items: center;
                gap: 24px;
                padding: 24px 16px;
            }
            .album-cover {
                width: 100%;
                max-width: 260px;
                height: 260px;
            }
            .album-title {
                font-size: 1.75rem;
            }
            .tracklist-header,
            .tracklist-row {
                grid-template-columns: 32px 36px 1fr auto;
            }
            .tracklist-header .col-actions,
            .track-row__actions {
                display: none;
            }
        }
    </style>
</head>
<body class="page">
<div class="dashboard">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="dashboard__main main">
        <header class="main__topbar topbar">
            <nav class="topbar__tabs tabs" aria-label="Разделы">
                <a class="tabs__item" href="/">Музыка</a>
                <a class="tabs__item" href="/podcasts">Подкасты</a>
                <a class="tabs__item" href="/broadcasts">Эфиры</a>
            </nav>

            <div class="topbar__actions">
                <form class="search js-search-form" role="search" aria-label="Поиск" action="/search" method="get">
                    <svg class="search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input class="search__field js-search-field" type="search" name="q"
                           placeholder="Искать в Richsound..." autocomplete="off">
                    <div class="search-dropdown js-search-dropdown" hidden></div>
                </form>

                <button class="theme-toggle-btn" data-theme-toggle aria-label="Переключить тему">
                    <span class="theme-icon" aria-hidden="true"></span>
                </button>

                <?php if ($isAuthenticated): ?>
                    <div class="topbar__profile">
                        <span class="topbar__profile-name"><?= $h((string) $userName) ?></span>
                        <span class="topbar__profile-role"><?= $h((string) ($user['email'] ?? '')) ?></span>
                    </div>
                <?php else: ?>
                    <a class="topbar__auth-link" href="/login" data-turbo="false">Войти</a>
                    <a class="topbar__auth-link topbar__auth-link--primary" href="/register" data-turbo="false">Регистрация</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="main__content">

            <!-- Album hero -->
            <div class="album-hero">
                <?php if ($hasCover): ?>
                    <div class="album-cover"
                         style="background-image: url('<?= $h($coverPath) ?>');"
                         role="img"
                         aria-label="Обложка альбома <?= $h($albumTitle) ?>"></div>
                <?php else: ?>
                    <div class="album-cover album-cover--placeholder" role="img" aria-label="Обложка альбома">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity:.3">
                            <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <div class="album-hero__info">
                    <span class="album-label">Альбом</span>
                    <h1 class="album-title"><?= $h($albumTitle) ?></h1>
                    <div class="album-hero__meta">
                        <a href="/authors/<?= $h($authorId) ?>"><?= $h($authorName) ?></a>
                        <?php if ($releasedFormatted !== ''): ?>
                            <span class="album-hero__meta__sep">·</span>
                            <span><?= $h($releasedFormatted) ?></span>
                        <?php endif; ?>
                        <span class="album-hero__meta__sep">·</span>
                        <span><?= $h($tracksCount) ?> <?= $tracksCount === 1 ? 'трек' : ($tracksCount >= 2 && $tracksCount <= 4 ? 'трека' : 'треков') ?></span>
                    </div>

                    <div class="album-hero__actions">
                        <?php if ($playlist !== []): ?>
                            <button class="btn-play-all js-play-track"
                                    type="button"
                                    data-track-index="0"
                                    aria-label="Воспроизвести альбом <?= $h($albumTitle) ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                                Слушать альбом
                            </button>
                        <?php else: ?>
                            <button class="btn-play-all" type="button" disabled>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                                Нет треков
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tracklist -->
            <div class="tracklist-section">
                <?php if ($tracks !== []): ?>
                    <div class="tracklist-header" aria-hidden="true">
                        <span>#</span>
                        <span></span>
                        <span>Название</span>
                        <span>Длит.</span>
                        <span class="col-actions"></span>
                    </div>
                    <?php foreach ($tracks as $rowIndex => $track): ?>
                        <?php
                            $tid          = (int) ($track['id'] ?? 0);
                            $tTitle       = (string) ($track['title'] ?? 'Без названия');
                            $tArtist      = (string) ($track['author_name'] ?? $authorName);
                            $tDuration    = (int) ($track['duration'] ?? 0);
                            $tCover       = $normalizeMediaUrl((string) ($track['cover_path'] ?? ''));
                            $tPlaysCount  = (int) ($track['plays_count'] ?? 0);
                            $tLikesCount  = (int) ($track['likes_count'] ?? 0);
                            $tIsLiked     = in_array($tid, $likedTrackIds, true);
                            $tPlayIndex   = $trackIndexById[$tid] ?? null;
                            $rowNum       = $rowIndex + 1;
                        ?>
                        <div class="tracklist-row" onclick="<?= $tPlayIndex !== null ? 'void(0)' : '' ?>">
                            <div class="track-row__num">
                                <span class="track-row__index"><?= $h($rowNum) ?></span>
                                <button class="track-row__play js-play-track"
                                        type="button"
                                        <?= $tPlayIndex !== null ? 'data-track-index="' . $h($tPlayIndex) . '"' : 'disabled' ?>
                                        aria-label="Воспроизвести <?= $h($tTitle) ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <polygon points="5 3 19 12 5 21 5 3"/>
                                    </svg>
                                </button>
                            </div>

                            <?php if ($tCover !== ''): ?>
                                <div class="track-row__thumb"
                                     style="background-image: url('<?= $h($tCover) ?>');"
                                     role="img"
                                     aria-label="<?= $h($tTitle) ?>"></div>
                            <?php else: ?>
                                <div class="track-row__thumb track-row__thumb--placeholder" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity:.4">
                                        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <div class="track-row__info">
                                <div class="track-row__title"><?= $h($tTitle) ?></div>
                                <div class="track-row__artist"><?= $h($tArtist) ?></div>
                            </div>

                            <div class="track-row__duration"><?= $h($formatDuration($tDuration)) ?></div>

                            <div class="track-row__actions" onclick="event.stopPropagation()">
                                <?php if ($isAuthenticated): ?>
                                    <button type="button"
                                            class="track-row__like js-like-btn<?= $tIsLiked ? ' track-row__like--active' : '' ?>"
                                            data-track-id="<?= $h($tid) ?>"
                                            aria-label="<?= $tIsLiked ? 'Убрать лайк' : 'Поставить лайк' ?>">
                                        <svg width="13" height="13" viewBox="0 0 24 24"
                                             fill="<?= $tIsLiked ? 'currentColor' : 'none' ?>"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                             aria-hidden="true" class="js-like-icon">
                                            <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                                        </svg>
                                        <span class="js-like-count"><?= $h($tLikesCount) ?></span>
                                    </button>

                                    <?php if ($userPlaylists !== []): ?>
                                        <div class="track-row__playlist-wrap">
                                            <button type="button"
                                                    class="track-row__add js-playlist-btn"
                                                    data-track-id="<?= $h($tid) ?>"
                                                    aria-label="Добавить в плейлист">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 5v14"/><path d="M5 12h14"/>
                                                </svg>
                                            </button>
                                            <ul class="playlist-dropdown" hidden>
                                                <?php foreach ($userPlaylists as $pl): ?>
                                                    <li>
                                                        <button type="button"
                                                                class="js-add-to-playlist"
                                                                data-playlist-id="<?= $h($pl['id']) ?>"
                                                                data-track-id="<?= $h($tid) ?>">
                                                            <?= $h($pl['title']) ?>
                                                        </button>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size:.8rem;color:var(--muted-soft)"><?= $h($tLikesCount) ?> ♥</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-tracklist">В этом альбоме пока нет треков.</div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php require __DIR__ . '/../partials/player-bar.php'; ?>
</div>

<?php require __DIR__ . '/../partials/mobile-player.php'; ?>
<?php require __DIR__ . '/../partials/player-config.php'; ?>
<script src="/assets/js/search.js"></script>
<script>
(function () {
    'use strict';

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ── Like buttons ────────────────────────────── */
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
                btn.classList.toggle('track-row__like--active', data.liked);
                btn.setAttribute('aria-label', data.liked ? 'Убрать лайк' : 'Поставить лайк');
                if (icon)  { icon.setAttribute('fill', data.liked ? 'currentColor' : 'none'); }
                if (count) { count.textContent = data.count; }
            })
            .catch(function () {});
        });
    });

    /* ── Playlist dropdowns ──────────────────────── */
    document.querySelectorAll('.js-playlist-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var wrap     = btn.closest('.track-row__playlist-wrap');
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
                var wrap = btn.closest('.track-row__playlist-wrap');
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
