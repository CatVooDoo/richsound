<?php

declare(strict_types=1);

$user          = is_array($user ?? null) ? $user : null;
$q             = trim((string) ($q ?? ''));
$tracks        = is_array($tracks ?? null) ? $tracks : [];
$authors       = is_array($authors ?? null) ? $authors : [];
$likedTrackIds = is_array($likedTrackIds ?? null) ? array_map('intval', $likedTrackIds) : [];
$userPlaylists = is_array($userPlaylists ?? null) ? $userPlaylists : [];
$isAuthenticated = $user !== null;

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
$coverStyle = static function (?string $path) use ($normalizeUrl, $h): string {
    $url = $normalizeUrl($path);
    if ($url === '') { return ''; }
    return ' style="background-image: linear-gradient(180deg, rgba(0,0,0,.28), rgba(0,0,0,.18)), url(\'' . $h($url) . '\');"';
};

$playlist       = [];
$trackIndexById = [];

foreach ($tracks as $track) {
    if (!is_array($track)) { continue; }
    $tid = (int) ($track['id'] ?? 0);
    if ($tid <= 0 || isset($trackIndexById[$tid])) { continue; }
    $playlist[] = [
        'id'       => $tid,
        'title'    => (string) ($track['title'] ?? ''),
        'artist'   => (string) ($track['author_name'] ?? ''),
        'authorId' => (int) ($track['author_id'] ?? 0),
        'album'    => (string) ($track['album_title'] ?? ''),
        'audioUrl' => '/player/stream?id=' . $tid,
        'coverUrl' => $normalizeUrl((string) ($track['cover_path'] ?? '')),
        'duration' => (int) ($track['duration'] ?? 0),
        'plays'    => (int) ($track['plays_count'] ?? 0),
        'likes'    => (int) ($track['likes_count'] ?? 0),
    ];
    $trackIndexById[$tid] = count($playlist) - 1;
}

$roleLabels = ['listener' => 'Слушатель', 'author' => 'Автор', 'admin' => 'Администратор'];
$userRole   = $isAuthenticated ? ($roleLabels[$user['role'] ?? ''] ?? 'Аккаунт') : 'Гость';
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $q !== '' ? $h('Поиск: ' . $q) . ' | Richsound' : 'Поиск | Richsound' ?></title>
    <script src="/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
</head>
<body class="page">
<div class="dashboard">
    <?php $navActive = 'search'; $brandMeta = $userRole; require __DIR__ . '/../partials/sidebar.php'; ?>

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
                           value="<?= $h($q) ?>"
                           placeholder="Искать в Richsound..."
                           autocomplete="off"
                           autofocus>
                    <div class="search-dropdown js-search-dropdown" hidden></div>
                </form>

                <button class="theme-toggle-btn" data-theme-toggle aria-label="Переключить тему">
                    <span class="theme-icon" aria-hidden="true"></span>
                </button>

                <?php if ($isAuthenticated): ?>
                    <div class="topbar__profile">
                        <span class="topbar__profile-name"><?= $h((string) ($user['name'] ?? '')) ?></span>
                        <span class="topbar__profile-role"><?= $h((string) ($user['email'] ?? '')) ?></span>
                    </div>
                <?php else: ?>
                    <a class="topbar__auth-link" href="/login" data-turbo="false">Войти</a>
                    <a class="topbar__auth-link topbar__auth-link--primary" href="/register" data-turbo="false">Регистрация</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="main__content">

            <?php if ($q === ''): ?>
                <div class="search-hero">
                    <svg class="search-hero__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <h2 class="search-hero__title">Найди свою музыку</h2>
                    <p class="search-hero__sub">Введи название трека или имя исполнителя в строку поиска выше</p>
                </div>

            <?php elseif ($tracks === [] && $authors === []): ?>
                <div class="search-hero">
                    <svg class="search-hero__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <h2 class="search-hero__title">Ничего не найдено</h2>
                    <p class="search-hero__sub">По запросу «<?= $h($q) ?>» ничего не нашлось. Попробуй другое слово.</p>
                </div>

            <?php else: ?>

                <?php if ($tracks !== []): ?>
                    <section class="section">
                        <div class="section__header">
                            <span class="section__marker"></span>
                            <h2 class="section__title">Треки</h2>
                            <span class="section__link"><?= $h(count($tracks)) ?> шт.</span>
                        </div>
                        <div class="section__albums album-grid">
                            <?php foreach ($tracks as $track): ?>
                                <?php
                                    $index   = $trackIndexById[(int) $track['id']] ?? null;
                                    $trackId = (int) $track['id'];
                                    $isLiked = in_array($trackId, $likedTrackIds, true);
                                ?>
                                <article class="album-card<?= $index !== null ? ' js-play-track' : '' ?>"
                                         <?= $index !== null ? 'data-track-index="' . $h((string) $index) . '" role="button" tabindex="0"' : '' ?>>
                                    <div class="album-card__art album-card__art--media"<?= $coverStyle((string) ($track['cover_path'] ?? '')) ?>>
                                        <button class="album-card__play" type="button"
                                                aria-label="Воспроизвести <?= $h($track['title']) ?>"
                                                <?= $index === null ? 'disabled' : '' ?>>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M8 5.14v13.72a1 1 0 0 0 1.5.87l10.74-6.86a1 1 0 0 0 0-1.74L9.5 4.27A1 1 0 0 0 8 5.14z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="album-card__meta">
                                        <h3 class="album-card__title">
                                            <a href="/tracks/<?= $h($trackId) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;" tabindex="-1">
                                                <?= $h($track['title']) ?>
                                            </a>
                                        </h3>
                                        <p class="album-card__artist">
                                            <a href="/authors/<?= $h((int) ($track['author_id'] ?? 0)) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;">
                                                <?= $h($track['author_name'] ?? '') ?>
                                            </a>
                                        </p>
                                        <p class="album-card__stats">
                                            <?= $h($formatDuration($track['duration'] ?? 0)) ?> · <?= $h((int) ($track['plays_count'] ?? 0)) ?> просл.
                                        </p>
                                        <div class="track-actions" onclick="event.stopPropagation()">
                                            <?php if ($isAuthenticated): ?>
                                                <button type="button"
                                                        class="track-actions__like js-like-btn<?= $isLiked ? ' track-actions__like--active' : '' ?>"
                                                        data-track-id="<?= $h($trackId) ?>"
                                                        aria-label="<?= $isLiked ? 'Убрать лайк' : 'Поставить лайк' ?>">
                                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                                         fill="<?= $isLiked ? 'currentColor' : 'none' ?>"
                                                         stroke="currentColor" stroke-width="2"
                                                         stroke-linecap="round" stroke-linejoin="round"
                                                         aria-hidden="true" class="js-like-icon">
                                                        <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"></path>
                                                    </svg>
                                                    <span class="js-like-count"><?= $h((int) ($track['likes_count'] ?? 0)) ?></span>
                                                </button>
                                                <?php if ($userPlaylists !== []): ?>
                                                    <div class="track-actions__playlist-wrap">
                                                        <button type="button" class="track-actions__add js-playlist-btn"
                                                                data-track-id="<?= $h($trackId) ?>"
                                                                aria-label="Добавить в плейлист">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M12 5v14"></path><path d="M5 12h14"></path>
                                                            </svg>
                                                        </button>
                                                        <ul class="playlist-dropdown" hidden>
                                                            <?php foreach ($userPlaylists as $pl): ?>
                                                                <li>
                                                                    <button type="button" class="js-add-to-playlist"
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
                                                <span class="track-actions__likes-count"><?= $h((int) ($track['likes_count'] ?? 0)) ?> ♥</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($authors !== []): ?>
                    <section class="section">
                        <div class="section__header">
                            <span class="section__marker"></span>
                            <h2 class="section__title">Авторы</h2>
                            <span class="section__link"><?= $h(count($authors)) ?> шт.</span>
                        </div>
                        <div class="search-authors-grid">
                            <?php foreach ($authors as $author): ?>
                                <a class="search-author-card" href="/authors/<?= $h((int) $author['id']) ?>">
                                    <div class="search-author-card__avatar"
                                         <?= !empty($author['avatar']) ? 'style="background-image:url(\'' . $h((string) $author['avatar']) . '\')"' : '' ?>>
                                        <?php if (empty($author['avatar'])): ?>
                                            <?= $h(mb_strtoupper(mb_substr((string) ($author['name'] ?? '?'), 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="search-author-card__info">
                                        <p class="search-author-card__name"><?= $h($author['name'] ?? '') ?></p>
                                        <p class="search-author-card__meta">
                                            <?= $h((int) ($author['tracks_count'] ?? 0)) ?> треков ·
                                            <?= $h((int) ($author['subscribers_count'] ?? 0)) ?> подписчиков
                                        </p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

<?php require __DIR__ . '/../partials/player-bar.php'; ?>
</div>

<?php require __DIR__ . '/../partials/mobile-player.php'; ?>

<?php require __DIR__ . '/../partials/player-config.php'; ?>
<script>
(function () {
    'use strict';
    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    document.querySelectorAll('.js-like-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var trackId = btn.dataset.trackId;
            fetch('/tracks/like', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body: new URLSearchParams({ track_id: trackId })
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
            fetch('/playlists/tracks/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body: new URLSearchParams({ playlist_id: btn.dataset.playlistId, track_id: btn.dataset.trackId })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var wrap = btn.closest('.track-actions__playlist-wrap');
                if (wrap) { var d = wrap.querySelector('.playlist-dropdown'); if (d) { d.hidden = true; } }
                if (data.added) { btn.textContent = '✓ ' + btn.textContent.replace(/^✓\s*/, ''); }
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
<script src="/assets/js/search.js"></script>
</body>
</html>
