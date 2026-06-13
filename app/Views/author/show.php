<?php

declare(strict_types=1);

$user         = is_array($user ?? null) ? $user : null;
$author       = is_array($author ?? null) ? $author : [];
$tracks       = is_array($tracks ?? null) ? $tracks : [];
$albums       = is_array($albums ?? null) ? $albums : [];
$isSubscribed = (bool) ($isSubscribed ?? false);

$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$formatDuration = static function (mixed $s): string {
    $t = max(0, (int) $s);
    return sprintf('%d:%02d', intdiv($t, 60), $t % 60);
};
$normalizeUrl = static function (?string $path): string {
    $path = trim((string) $path);
    if ($path === '') { return ''; }
    if (preg_match('~^https?://~i', $path) === 1 || str_starts_with($path, '/')) { return $path; }
    return '/' . ltrim($path, '/');
};

$playlist       = [];
$trackIndexById = [];

foreach ($tracks as $track) {
    $tid      = (int) ($track['id'] ?? 0);
    $audioUrl = $normalizeUrl((string) ($track['file_path'] ?? ''));
    if ($tid <= 0 || $audioUrl === '' || isset($trackIndexById[$tid])) { continue; }
    $playlist[] = [
        'id'       => $tid,
        'title'    => (string) ($track['title'] ?? ''),
        'artist'   => (string) ($track['author_name'] ?? ''),
        'authorId' => (int) ($author['id'] ?? 0),
        'album'    => (string) ($track['album_title'] ?? ''),
        'audioUrl' => '/player/stream?id=' . $tid,
        'coverUrl' => $normalizeUrl((string) ($track['cover_path'] ?? '')),
        'duration' => (int) ($track['duration'] ?? 0),
        'plays'    => (int) ($track['plays_count'] ?? 0),
        'likes'    => (int) ($track['likes_count'] ?? 0),
    ];
    $trackIndexById[$tid] = count($playlist) - 1;
}

$authorId   = (int) ($author['id'] ?? 0);
$authorName = (string) ($author['name'] ?? 'Автор');
$isSelf     = $user !== null && (int) $user['id'] === $authorId;

$isAuthenticated = $user !== null;
$userName        = $user['name'] ?? null;
$roleLabels      = ['listener' => 'Слушатель', 'author' => 'Автор', 'admin' => 'Администратор'];
$userRole        = $isAuthenticated ? ($roleLabels[$user['role'] ?? ''] ?? 'Аккаунт') : 'Гость';

// Context for the shared sidebar partial (no nav item matches an artist page).
$navActive = '';
$brandMeta = $userRole;
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($authorName) ?> | Richsound</title>
    <script src="/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <style>
        /* ── Artist profile — hero + content blocks living inside the
              dashboard shell's .main__content (home.css owns the shell). ── */

        /* Rounded hero header (was a full-bleed banner before the page got
           the .dashboard shell). */
        .ap-hero {
            display: flex; align-items: flex-end; gap: 28px;
            position: relative; overflow: hidden;
            padding: 36px 36px 32px;
            border-radius: var(--radius-xl, 32px);
            background: linear-gradient(180deg, #1a0a2e 0%, rgba(14, 14, 22, 0.45) 100%);
            border: 1px solid var(--stroke);
        }
        .ap-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 20% 50%, rgba(139,92,246,.25) 0%, transparent 70%);
            pointer-events: none;
        }
        .ap-hero > * { position: relative; }

        .ap-avatar {
            width: 180px; height: 180px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, #27131c 0%, #3b1d4e 50%, #8b5cf6 100%);
            background-size: cover; background-position: center;
            box-shadow: 0 16px 48px rgba(139,92,246,.45), 0 0 0 1px rgba(139,92,246,.2) inset;
            border: 3px solid rgba(139,92,246,.45);
        }

        .ap-hero-info { flex: 1; min-width: 0; padding-bottom: 4px; }

        .ap-hero-label {
            font-size: 11px; text-transform: uppercase; letter-spacing: .12em;
            color: rgba(255,255,255,.45); margin: 0 0 10px;
        }

        .ap-hero-name {
            font-size: clamp(32px, 5vw, 60px); font-weight: 700; line-height: 1.05;
            color: #fff; margin: 0 0 16px; letter-spacing: -.02em;
        }

        .ap-hero-stats {
            display: flex; gap: 24px; flex-wrap: wrap;
            font-size: 14px; color: rgba(255,255,255,.55);
            margin-bottom: 24px;
        }
        .ap-hero-stats span strong { color: #fff; font-weight: 600; }

        .ap-hero-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .ap-hero-bio { font-size: 14px; color: rgba(255,255,255,.55); max-width: 560px; line-height: 1.6; margin: 12px 0; }

        .ap-play-btn {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s;
            box-shadow: 0 6px 28px rgba(139,92,246,.55);
            flex-shrink: 0;
        }
        .ap-play-btn:hover { transform: scale(1.08); box-shadow: 0 10px 36px rgba(139,92,246,.65); }
        .ap-play-btn:active { transform: scale(.95); }
        .ap-play-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(139,92,246,.55), 0 6px 28px rgba(139,92,246,.5); }

        .btn-subscribe {
            padding: 11px 28px; border-radius: 999px; border: 1.5px solid rgba(255,255,255,.28);
            background: transparent; color: #fff; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: border-color .2s, background .2s, box-shadow .2s;
            font-family: inherit; letter-spacing: .01em;
        }
        .btn-subscribe:hover { border-color: rgba(255,255,255,.8); background: rgba(255,255,255,.07); }
        .btn-subscribe:active { background: rgba(255,255,255,.04); }
        .btn-subscribe:focus-visible { outline: none; box-shadow: 0 0 0 2px rgba(255,255,255,.35); }
        .btn-subscribe.subscribed {
            border-color: rgba(255,255,255,.14); color: rgba(255,255,255,.55);
        }
        .btn-subscribe.subscribed:hover { border-color: rgba(255,255,255,.7); color: #fff; }

        .ap-login-hint { font-size: 13px; color: rgba(255,255,255,.35); }
        .ap-login-hint a { color: #a78bfa; text-decoration: none; transition: color .15s; }
        .ap-login-hint a:hover { color: #c4b5fd; }
        .ap-login-hint a:focus-visible { outline: none; text-decoration: underline; text-underline-offset: 2px; }

        /* ── section headers ────────────────────────────────── */
        .ap-section { margin-top: 40px; }
        .ap-section-head {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px; padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .ap-section-head h2 { font-size: 20px; font-weight: 700; color: #fff; margin: 0; }
        .ap-section-count { font-size: 13px; color: rgba(255,255,255,.35); margin-left: auto; }

        /* ── tracks list ────────────────────────────────────── */
        .ap-tracks { display: flex; flex-direction: column; }

        .ap-track {
            display: grid;
            grid-template-columns: 36px 56px 1fr auto auto;
            align-items: center; gap: 14px;
            padding: 10px 14px; border-radius: 10px;
            cursor: pointer; transition: background .15s;
        }
        .ap-track:hover { background: rgba(255,255,255,.06); }
        .ap-track:focus-visible { outline: none; background: rgba(255,255,255,.07); box-shadow: inset 0 0 0 1px rgba(139,92,246,.3); }
        .ap-track:hover .ap-track__num { opacity: 0; }
        .ap-track:hover .ap-track__play { opacity: 1; }

        .ap-track__num-wrap { position: relative; width: 36px; height: 36px; flex-shrink: 0; }
        .ap-track__num, .ap-track__play {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            transition: opacity .15s;
        }
        .ap-track__num { font-size: 14px; color: rgba(255,255,255,.4); font-variant-numeric: tabular-nums; }
        .ap-track__play { opacity: 0; color: #fff; }

        .ap-track__cover {
            width: 56px; height: 56px; border-radius: 6px; flex-shrink: 0;
            background: linear-gradient(135deg, #1a0a2e, #3b1d4e);
            background-size: cover; background-position: center;
        }
        .ap-track__info { min-width: 0; }
        .ap-track__title {
            font-size: 14px; font-weight: 600; color: #fff; margin: 0 0 3px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ap-track__sub {
            font-size: 12px; color: rgba(255,255,255,.4); margin: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ap-track__dur { font-size: 13px; color: rgba(255,255,255,.4); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .ap-track__likes { font-size: 13px; color: rgba(255,255,255,.3); white-space: nowrap; }

        /* ── albums grid ────────────────────────────────────── */
        .ap-albums {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }
        .ap-album {
            background: rgba(255,255,255,.04); border-radius: 12px; padding: 16px;
            text-decoration: none; display: block; transition: background .2s;
        }
        .ap-album:hover { background: rgba(255,255,255,.09); }
        .ap-album:focus-visible { outline: none; box-shadow: 0 0 0 2px rgba(139,92,246,.45); }
        .ap-album__cover {
            width: 100%; aspect-ratio: 1; border-radius: 8px; margin-bottom: 12px;
            background: linear-gradient(135deg, #1a0a2e 0%, #3b1d4e 50%, #c4843d 100%);
            background-size: cover; background-position: center;
        }
        .ap-album__title {
            font-size: 14px; font-weight: 600; color: #fff; margin: 0 0 4px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ap-album__meta { font-size: 12px; color: rgba(255,255,255,.4); margin: 0; }

        .ap-empty { color: rgba(255,255,255,.35); font-size: 14px; padding: 20px 0; }

        /* ── responsive ─────────────────────────────────────── */
        @media (max-width: 640px) {
            .ap-hero { flex-direction: column; align-items: center; text-align: center; padding: 24px 20px 28px; }
            .ap-avatar { width: 140px; height: 140px; }
            .ap-hero-stats { justify-content: center; }
            .ap-hero-actions { justify-content: center; }
            .ap-hero-name { font-size: clamp(26px, 8vw, 40px); }
            .ap-track { grid-template-columns: 28px 44px 1fr auto; }
            .ap-track__likes { display: none; }
        }

        /* ── light theme ────────────────────────────────────────
           Palette matches home.css [data-theme="light"]. The avatar and
           cover gradients stand in for artwork and keep their colours. */
        [data-theme="light"] .ap-hero {
            background: linear-gradient(180deg, #e9e4f7 0%, #ffffff 100%);
        }
        [data-theme="light"] .ap-hero::before {
            background: radial-gradient(ellipse 80% 60% at 20% 50%, rgba(139,92,246,.14) 0%, transparent 70%);
        }

        [data-theme="light"] .ap-hero-label { color: rgba(20,16,40,.45); }
        [data-theme="light"] .ap-hero-name  { color: #16131f; }
        [data-theme="light"] .ap-hero-stats { color: rgba(20,16,40,.55); }
        [data-theme="light"] .ap-hero-stats span strong { color: #16131f; }
        [data-theme="light"] .ap-hero-bio   { color: rgba(20,16,40,.55); }

        [data-theme="light"] .btn-subscribe {
            border-color: rgba(20,16,40,.28); color: #16131f;
        }
        [data-theme="light"] .btn-subscribe:hover { border-color: rgba(20,16,40,.8); background: rgba(20,16,40,.05); }
        [data-theme="light"] .btn-subscribe:active { background: rgba(20,16,40,.03); }
        [data-theme="light"] .btn-subscribe:focus-visible { box-shadow: 0 0 0 2px rgba(20,16,40,.3); }
        [data-theme="light"] .btn-subscribe.subscribed {
            border-color: rgba(20,16,40,.14); color: rgba(20,16,40,.55);
        }
        [data-theme="light"] .btn-subscribe.subscribed:hover { border-color: rgba(20,16,40,.7); color: #16131f; }

        [data-theme="light"] .ap-login-hint { color: rgba(20,16,40,.45); }
        [data-theme="light"] .ap-login-hint a { color: #6d28d9; }
        [data-theme="light"] .ap-login-hint a:hover { color: #5b21b6; }

        [data-theme="light"] .ap-section-head { border-bottom-color: rgba(20,16,40,.08); }
        [data-theme="light"] .ap-section-head h2 { color: #16131f; }
        [data-theme="light"] .ap-section-count { color: rgba(20,16,40,.4); }

        [data-theme="light"] .ap-track:hover { background: rgba(20,16,40,.05); }
        [data-theme="light"] .ap-track:focus-visible { background: rgba(20,16,40,.06); }
        [data-theme="light"] .ap-track__num   { color: rgba(20,16,40,.45); }
        [data-theme="light"] .ap-track__play  { color: #16131f; }
        [data-theme="light"] .ap-track__title { color: #16131f; }
        [data-theme="light"] .ap-track__sub   { color: rgba(20,16,40,.45); }
        [data-theme="light"] .ap-track__dur   { color: rgba(20,16,40,.45); }
        [data-theme="light"] .ap-track__likes { color: rgba(20,16,40,.35); }

        [data-theme="light"] .ap-album {
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(20,16,40,.08);
        }
        [data-theme="light"] .ap-album:hover { background: #faf9fe; box-shadow: 0 0 0 1px rgba(139,92,246,.25), 0 8px 28px rgba(76,60,130,.12); }
        [data-theme="light"] .ap-album__title { color: #16131f; }
        [data-theme="light"] .ap-album__meta  { color: rgba(20,16,40,.45); }

        [data-theme="light"] .ap-empty { color: rgba(20,16,40,.45); }
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

            <div class="ap-hero">
                <div class="ap-avatar"<?= !empty($author['avatar']) ? ' style="background-image:url(\'' . $h((string) $author['avatar']) . '\')"' : '' ?>></div>

                <div class="ap-hero-info">
                    <p class="ap-hero-label">Исполнитель</p>
                    <h1 class="ap-hero-name"><?= $h($authorName) ?></h1>

                    <div class="ap-hero-stats">
                        <span><strong><?= $h((int) ($author['tracks_count'] ?? 0)) ?></strong> треков</span>
                        <span><strong><?= $h((int) ($author['albums_count'] ?? 0)) ?></strong> альбомов</span>
                        <span><strong><?= $h((int) ($author['subscribers_count'] ?? 0)) ?></strong> подписчиков</span>
                    </div>

                    <?php if (!empty($author['bio'])): ?>
                        <p class="ap-hero-bio"><?= $h($author['bio']) ?></p>
                    <?php endif; ?>

                    <div class="ap-hero-actions">
                        <?php if ($playlist !== []): ?>
                            <button class="ap-play-btn js-play-track" type="button"
                                    data-track-index="0" aria-label="Слушать треки автора">
                                <svg class="player__icon-play" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <polygon points="6 3 20 12 6 21 6 3"/>
                                </svg>
                                <svg class="player__icon-pause" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <rect x="6" y="4" width="4" height="16" rx="2"/><rect x="14" y="4" width="4" height="16" rx="2"/>
                                </svg>
                            </button>
                        <?php endif; ?>

                        <?php if ($user !== null && !$isSelf): ?>
                            <button type="button"
                                    class="btn-subscribe js-subscribe<?= $isSubscribed ? ' subscribed' : '' ?>"
                                    data-author-id="<?= $h($authorId) ?>"
                                    data-subscribed="<?= $isSubscribed ? '1' : '0' ?>">
                                <?= $isSubscribed ? 'Подписан' : 'Подписаться' ?>
                            </button>
                        <?php elseif ($user === null): ?>
                            <span class="ap-login-hint"><a href="/login" data-turbo="false">Войди</a>, чтобы подписаться</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tracks -->
            <div class="ap-section">
                <div class="ap-section-head">
                    <h2>Треки</h2>
                    <span class="ap-section-count"><?= $h(count($tracks)) ?> шт.</span>
                </div>

                <?php if ($tracks !== []): ?>
                    <div class="ap-tracks">
                        <?php foreach ($tracks as $i => $track): ?>
                            <?php $tid = (int) ($track['id'] ?? 0); $idx = $trackIndexById[$tid] ?? null; ?>
                            <div class="ap-track<?= $idx !== null ? ' js-play-track' : '' ?>"
                                 <?= $idx !== null ? 'data-track-index="' . $h((string) $idx) . '" tabindex="0" role="button"' : '' ?>>
                                <div class="ap-track__num-wrap">
                                    <span class="ap-track__num"><?= $h($i + 1) ?></span>
                                    <span class="ap-track__play">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    </span>
                                </div>
                                <div class="ap-track__cover"<?= !empty($track['cover_path']) ? ' style="background-image:url(\'' . $h((string) $track['cover_path']) . '\')"' : '' ?>></div>
                                <div class="ap-track__info">
                                    <p class="ap-track__title"><?= $h($track['title']) ?></p>
                                    <?php if (!empty($track['album_title'])): ?>
                                        <p class="ap-track__sub"><?= $h($track['album_title']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="ap-track__dur"><?= $h($formatDuration($track['duration'] ?? 0)) ?></span>
                                <span class="ap-track__likes">♥ <?= $h((int) ($track['likes_count'] ?? 0)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="ap-empty">У этого автора пока нет треков.</p>
                <?php endif; ?>
            </div>

            <!-- Albums -->
            <?php if ($albums !== []): ?>
                <div class="ap-section">
                    <div class="ap-section-head">
                        <h2>Альбомы</h2>
                        <span class="ap-section-count"><?= $h(count($albums)) ?> шт.</span>
                    </div>
                    <div class="ap-albums">
                        <?php foreach ($albums as $album): ?>
                            <a class="ap-album" href="/albums/<?= $h($album['id']) ?>">
                                <div class="ap-album__cover"<?= !empty($album['cover_path']) ? ' style="background-image:url(\'' . $h((string) $album['cover_path']) . '\')"' : '' ?>></div>
                                <p class="ap-album__title"><?= $h($album['title']) ?></p>
                                <p class="ap-album__meta">
                                    <?= $h((int) $album['tracks_count']) ?> треков
                                    <?= !empty($album['released_at']) ? ' · ' . $h(substr((string) $album['released_at'], 0, 4)) : '' ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

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
    var btn = document.querySelector('.js-subscribe');
    if (!btn) { return; }

    btn.addEventListener('click', function () {
        var authorId = btn.dataset.authorId;

        fetch('/authors/subscribe', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
            body:    new URLSearchParams({ author_id: authorId })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) { return; }
            btn.dataset.subscribed = data.subscribed ? '1' : '0';
            btn.classList.toggle('subscribed', data.subscribed);
            btn.textContent = data.subscribed ? 'Подписан' : 'Подписаться';
        })
        .catch(function () {});
    });
}());
</script>
</body>
</html>
