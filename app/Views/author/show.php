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
        /* ── page shell ─────────────────────────────────────── */
        .ap-wrap {
            min-height: 100vh;
            background: #0a0a12;
            padding-bottom: 100px; /* space for fixed player */
            font-family: "Sora", "Segoe UI", system-ui, sans-serif;
            color: #fff;
        }

        /* gradient banner behind hero */
        .ap-banner {
            width: 100%;
            min-height: 280px;
            background: linear-gradient(180deg, #1a0a2e 0%, #0a0a12 100%);
            position: relative;
        }
        .ap-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 20% 50%, rgba(139,92,246,.25) 0%, transparent 70%);
            pointer-events: none;
        }

        .ap-inner { max-width: 1000px; margin: 0 auto; padding: 0 32px; }

        /* ── top line: back link + theme toggle ─────────────── */
        .ap-topline {
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 24px;
        }
        .ap-back {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,.5); font-size: 13px; text-decoration: none;
            transition: color .15s;
        }
        .ap-back:hover { color: #fff; }

        /* ── hero ───────────────────────────────────────────── */
        .ap-hero {
            display: flex; align-items: flex-end; gap: 28px;
            padding: 24px 0 32px;
        }

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
            font-size: clamp(32px, 5vw, 64px); font-weight: 700; line-height: 1.05;
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

        .ap-back:focus-visible { outline: none; color: #fff; text-decoration: underline; text-underline-offset: 3px; }

        .ap-login-hint { font-size: 13px; color: rgba(255,255,255,.35); }
        .ap-login-hint a { color: #a78bfa; text-decoration: none; transition: color .15s; }
        .ap-login-hint a:hover { color: #c4b5fd; }
        .ap-login-hint a:focus-visible { outline: none; text-decoration: underline; text-underline-offset: 2px; }

        /* ── content area ───────────────────────────────────── */
        .ap-content { padding: 0 0 40px; }

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
            .ap-inner { padding: 0 16px; }
            .ap-hero { flex-direction: column; align-items: center; text-align: center; padding: 20px 0 28px; }
            .ap-avatar { width: 140px; height: 140px; }
            .ap-hero-stats { justify-content: center; }
            .ap-hero-actions { justify-content: center; }
            .ap-hero-name { font-size: clamp(26px, 8vw, 40px); }
            .ap-track { grid-template-columns: 28px 44px 1fr auto; }
            .ap-track__likes { display: none; }
        }

        /* ── light theme ────────────────────────────────────────
           Palette matches home.css [data-theme="light"]: lavender-
           white surface, near-black ink. Avatar and cover gradients
           stand in for artwork and keep their colours. */
        [data-theme="light"] .ap-wrap {
            background: #f1eff8;
            color: #16131f;
        }
        [data-theme="light"] .ap-banner {
            background: linear-gradient(180deg, #e9e4f7 0%, #f1eff8 100%);
        }
        [data-theme="light"] .ap-banner::before {
            background: radial-gradient(ellipse 80% 60% at 20% 50%, rgba(139,92,246,.14) 0%, transparent 70%);
        }

        [data-theme="light"] .ap-back { color: rgba(20,16,40,.5); }
        [data-theme="light"] .ap-back:hover,
        [data-theme="light"] .ap-back:focus-visible { color: #16131f; }

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
<body class="ap-wrap">

<div class="ap-banner">
    <div class="ap-inner">
        <div class="ap-topline">
            <a class="ap-back" href="/">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                На главную
            </a>
            <button class="theme-toggle-btn" data-theme-toggle aria-label="Переключить тему">
                <span class="theme-icon" aria-hidden="true"></span>
            </button>
        </div>

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
                        <span class="ap-login-hint"><a href="/login">Войди</a>, чтобы подписаться</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content -->
<div class="ap-inner ap-content">

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

<!-- Fixed player -->
<footer class="player-bar player-bar--fixed" data-player-root aria-label="Музыкальный плеер">
    <div class="player__now">
        <div class="player__art" data-player-cover></div>
        <div class="player__info">
            <p class="player__title" data-player-title>Трек не выбран</p>
            <p class="player__artist" data-player-artist>Выбери трек</p>
        </div>
        <button class="player__btn player__btn--heart" type="button" aria-label="В избранное">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/></svg>
        </button>
    </div>
    <div class="player__center">
        <div class="player__controls">
            <button class="player__btn player__btn--shuffle" type="button" tabindex="-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
            </button>
            <button class="player__btn player__btn--prev" type="button" data-player-prev aria-label="Предыдущий трек">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="19 20 9 12 19 4 19 20"/><rect x="5" y="4" width="2" height="16" rx="1"/></svg>
            </button>
            <button class="player__btn player__btn--play" type="button" data-player-toggle aria-label="Воспроизвести">
                <svg class="player__icon-play" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <svg class="player__icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="4" width="4" height="16" rx="2"/><rect x="14" y="4" width="4" height="16" rx="2"/></svg>
            </button>
            <button class="player__btn player__btn--next" type="button" data-player-next aria-label="Следующий трек">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 4 15 12 5 20 5 4"/><rect x="17" y="4" width="2" height="16" rx="1"/></svg>
            </button>
            <button class="player__btn player__btn--repeat" type="button" tabindex="-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
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
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
        </button>
        <div class="player__slider player__slider--vol" data-player-slider-volume>
            <div class="player__slider-rail"></div>
            <div class="player__slider-fill"></div>
            <input class="player__range" type="range" min="0" max="1" step="0.01" value="0.8" data-player-volume aria-label="Громкость">
        </div>
    </div>
</footer>

<?php require __DIR__ . '/../partials/mobile-player.php'; ?>

<?php require __DIR__ . '/../partials/player-config.php'; ?>
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
