<?php

declare(strict_types=1);

$user          = is_array($user ?? null) ? $user : [];
$likedTracks   = is_array($likedTracks ?? null) ? $likedTracks : [];
$playlists     = is_array($playlists ?? null) ? $playlists : [];
$subscriptions = is_array($subscriptions ?? null) ? $subscriptions : [];

$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$formatDuration = static function (mixed $s): string {
    $t = max(0, (int) $s);
    return sprintf('%d:%02d', intdiv($t, 60), $t % 60);
};

/* Build player playlist from liked tracks */
$playerPlaylist   = [];
$trackIndexById   = [];
foreach ($likedTracks as $track) {
    $tid = (int) ($track['id'] ?? 0);
    if ($tid <= 0 || isset($trackIndexById[$tid])) { continue; }
    $playerPlaylist[] = [
        'id'       => $tid,
        'title'    => (string) ($track['title'] ?? 'Без названия'),
        'artist'   => (string) ($track['author_name'] ?? 'Неизвестный исполнитель'),
        'album'    => (string) ($track['album_title'] ?? ''),
        'audioUrl' => '/player/stream?id=' . $tid,
        'coverUrl' => (string) ($track['cover_path'] ?? ''),
        'duration' => (int) ($track['duration'] ?? 0),
        'plays'    => (int) ($track['plays_count'] ?? 0),
        'likes'    => (int) ($track['likes_count'] ?? 0),
    ];
    $trackIndexById[$tid] = count($playerPlaylist) - 1;
}

/* gradient class for playlists without a cover */
$gradients = ['gradient2', 'gradient3', 'gradient4'];
$gradientFor = static function (int $id) use ($gradients): string {
    return $gradients[$id % count($gradients)];
};
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медиатека | Richsound</title>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
</head>
<body class="page">
<div class="dashboard">
    <aside class="dashboard__sidebar sidebar">
        <div class="sidebar__brand brand">
            <div class="brand__title">Richsound</div>
            <div class="brand__meta">Медиатека</div>
        </div>
        <nav class="sidebar__nav" aria-label="Навигация">
            <a class="sidebar__link" href="/">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5"></path><path d="M5 9.5V21h14V9.5"></path><path d="M9 21v-6h6v6"></path>
                </svg>
                <span class="sidebar__text">Главная</span>
            </a>
            <a class="sidebar__link" href="/search">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <span class="sidebar__text">Поиск</span>
            </a>
            <a class="sidebar__link sidebar__link--active" href="/library">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v16H6.5A2.5 2.5 0 0 0 4 22z"></path><path d="M8 4v16"></path>
                </svg>
                <span class="sidebar__text">Медиатека</span>
            </a>
            <a class="sidebar__link" href="/podcasts">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"></path>
                    <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                </svg>
                <span class="sidebar__text">Подкасты</span>
                <span class="sidebar__soon">Скоро</span>
            </a>
            <a class="sidebar__link" href="/broadcasts">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                    <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                    <circle cx="12" cy="20" r="1" fill="currentColor"></circle>
                </svg>
                <span class="sidebar__text">Эфиры</span>
                <span class="sidebar__soon">Скоро</span>
            </a>
        </nav>
        <div class="sidebar__group">
            <div class="sidebar__label">Аккаунт</div>
            <?php if (in_array($user['role'] ?? null, ['author', 'admin'], true)): ?>
                <a class="sidebar__link" href="/author">
                    <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2a3 3 0 0 1 3 3v7a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><path d="M12 19v3"></path><path d="M8 22h8"></path>
                    </svg>
                    <span class="sidebar__text">Кабинет автора</span>
                </a>
            <?php endif; ?>
            <?php if (($user['role'] ?? null) === 'admin'): ?>
                <a class="sidebar__link" href="/admin">
                    <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M7 8h10"></path><path d="M7 12h10"></path><path d="M7 16h6"></path>
                    </svg>
                    <span class="sidebar__text">Админка</span>
                </a>
            <?php endif; ?>
        </div>
        <form action="/logout" method="post">
            <?= \App\Core\Csrf::field() ?>
            <button class="sidebar__upgrade" type="submit">Выйти</button>
        </form>
    </aside>

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
                <div class="topbar__profile">
                    <span class="topbar__profile-name"><?= $h($user['name'] ?? '') ?></span>
                    <span class="topbar__profile-role"><?= $h($user['email'] ?? '') ?></span>
                </div>
            </div>
        </header>

        <div class="main__content">
            <?php if (!empty($success)): ?>
                <div class="lib-alert lib-alert--success"><?= $h($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="lib-alert lib-alert--error"><?= $h($error) ?></div>
            <?php endif; ?>

            <div class="medialib">

                <!-- ── Head ───────────────────────────────── -->
                <div class="medialib__head">
                    <h2 class="medialib__title">Медиатека</h2>
                    <button class="medialib__add-btn" id="js-medialib-plus" type="button" title="Создать плейлист" aria-label="Создать плейлист">+</button>
                </div>

                <!-- ── Filter chips ───────────────────────── -->
                <div class="medialib__filters" role="group" aria-label="Фильтр">
                    <button class="medialib__chip medialib__chip--active" data-filter="all" type="button">Все</button>
                    <button class="medialib__chip" data-filter="playlist" type="button">Плейлисты</button>
                    <button class="medialib__chip" data-filter="artist" type="button">Артисты</button>
                </div>

                <!-- ── Search ─────────────────────────────── -->
                <div class="medialib__search-wrap">
                    <svg class="medialib__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input class="medialib__search" id="js-medialib-search" type="search"
                           placeholder="Фильтровать библиотеку…" autocomplete="off" aria-label="Поиск внутри медиатеки">
                </div>

                <!-- ── Inline create-playlist form ────────── -->
                <form class="medialib__create-form" id="js-medialib-create-form" action="/playlists/create" method="post" hidden>
                    <?= \App\Core\Csrf::field() ?>
                    <input type="text" name="title" placeholder="Название плейлиста" maxlength="255" required autocomplete="off">
                    <select name="is_public">
                        <option value="1">Публичный</option>
                        <option value="0">Приватный</option>
                    </select>
                    <button type="submit">Создать</button>
                </form>

                <!-- ── Rows list ──────────────────────────── -->
                <div class="medialib__list" id="js-medialib-list">

                    <!-- Liked songs card (always first) -->
                    <div class="medialib__row" data-filter="playlist" data-name="понравившееся" id="js-liked-card" style="cursor:pointer">
                        <div class="medialib__art medialib__art--gradient" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
                                <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                            </svg>
                        </div>
                        <div class="medialib__info">
                            <span class="medialib__name">Понравившееся</span>
                            <span class="medialib__sub">Плейлист · <?= $h(count($likedTracks)) ?> <?= count($likedTracks) === 1 ? 'трек' : 'треков' ?></span>
                        </div>
                        <svg id="js-liked-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color:rgba(255,255,255,.4);flex-shrink:0;transition:transform .2s">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>

                    <!-- Expandable liked tracks panel -->
                    <div class="medialib__liked-panel" id="js-liked-panel" hidden>
                        <?php if ($likedTracks !== []): ?>
                            <?php foreach ($likedTracks as $track): ?>
                                <?php $tIdx = $trackIndexById[(int) ($track['id'] ?? 0)] ?? null; ?>
                                <div class="liked-row js-play-track"
                                     <?= $tIdx !== null ? 'data-track-index="' . $tIdx . '"' : '' ?>
                                     role="button" tabindex="0" style="cursor:pointer">
                                    <div class="liked-row__cover"<?= !empty($track['cover_path']) ? ' style="background-image:url(\'' . $h((string) $track['cover_path']) . '\')"' : '' ?>></div>
                                    <div class="liked-row__info">
                                        <p class="liked-row__title"><?= $h($track['title']) ?></p>
                                        <p class="liked-row__meta">
                                            <?= $h($track['author_name'] ?? '') ?><?= !empty($track['album_title']) ? ' · ' . $h($track['album_title']) : '' ?> · <?= $h($formatDuration($track['duration'] ?? 0)) ?>
                                        </p>
                                    </div>
                                    <button type="button"
                                            class="liked-row__unlike js-unlike-btn"
                                            data-track-id="<?= $h($track['id']) ?>"
                                            aria-label="Убрать из любимых">♥</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="medialib__empty" style="padding:16px 14px">
                                Ты ещё не поставил ни одного лайка. Заходи на <a href="/">главную</a> и лайкай треки.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Playlist rows -->
                    <?php foreach ($playlists as $pl): ?>
                        <?php
                            $plId    = (int) ($pl['id'] ?? 0);
                            $plTitle = (string) ($pl['title'] ?? '');
                            $plCount = (int) ($pl['tracks_count'] ?? 0);
                            $isPublic = (bool) ($pl['is_public'] ?? true);
                            $cover   = !empty($pl['cover_path']) ? (string) $pl['cover_path'] : '';
                            $gradCls = $gradientFor($plId);
                        ?>
                        <div class="medialib__row" data-filter="playlist" data-name="<?= $h(mb_strtolower($plTitle)) ?>">
                            <a href="/playlists/<?= $h($plId) ?>" class="medialib__art medialib__art--<?= $cover ? '' : $h($gradCls) ?>"
                               style="<?= $cover ? 'background-image:url(\'' . $h($cover) . '\')' : '' ?>;text-decoration:none" tabindex="-1" aria-hidden="true"></a>
                            <a href="/playlists/<?= $h($plId) ?>" class="medialib__info" style="text-decoration:none">
                                <span class="medialib__name"><?= $h($plTitle) ?></span>
                                <span class="medialib__sub">Плейлист · <?= $h($plCount) ?> <?= $plCount === 1 ? 'трек' : 'треков' ?></span>
                            </a>
                            <span class="medialib__badge<?= $isPublic ? '' : ' medialib__badge--private' ?>">
                                <?= $isPublic ? 'Публичный' : 'Приватный' ?>
                            </span>
                            <div class="medialib__dot-menu">
                                <button class="medialib__dot-btn js-dot-btn" type="button" aria-label="Действия" aria-expanded="false">···</button>
                                <div class="medialib__ctx-menu" hidden>
                                    <a href="/playlists/<?= $h($plId) ?>">Открыть</a>
                                    <form action="/playlists/delete" method="post"
                                          onsubmit="return confirm('Удалить плейлист «<?= $h(addslashes($plTitle)) ?>»?')">
                                        <?= \App\Core\Csrf::field() ?>
                                        <input type="hidden" name="playlist_id" value="<?= $h($plId) ?>">
                                        <button type="submit" class="danger">Удалить</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Artist rows (subscriptions) -->
                    <?php foreach ($subscriptions as $author): ?>
                        <?php
                            $authId   = (int) ($author['id'] ?? 0);
                            $authName = (string) ($author['name'] ?? '');
                            $initial  = mb_strtoupper(mb_substr($authName, 0, 1));
                            $avatar   = !empty($author['avatar']) ? (string) $author['avatar'] : '';
                            $subs     = (int) ($author['subscribers_count'] ?? 0);
                        ?>
                        <div class="medialib__row" data-filter="artist" data-name="<?= $h(mb_strtolower($authName)) ?>">
                            <a href="/authors/<?= $h($authId) ?>" class="medialib__art medialib__art--round"
                               style="<?= $avatar ? 'background-image:url(\'' . $h($avatar) . '\')' : '' ?>;text-decoration:none" tabindex="-1" aria-hidden="true">
                                <?php if (!$avatar): ?><?= $h($initial) ?><?php endif; ?>
                            </a>
                            <a href="/authors/<?= $h($authId) ?>" class="medialib__info" style="text-decoration:none">
                                <span class="medialib__name"><?= $h($authName) ?></span>
                                <span class="medialib__sub">Исполнитель · <?= $h($subs) ?> подп.</span>
                            </a>
                            <div class="medialib__dot-menu">
                                <button class="medialib__dot-btn js-dot-btn" type="button" aria-label="Действия" aria-expanded="false">···</button>
                                <div class="medialib__ctx-menu" hidden>
                                    <a href="/authors/<?= $h($authId) ?>">Страница артиста</a>
                                    <button type="button" class="danger js-unsubscribe" data-author-id="<?= $h($authId) ?>">Отписаться</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Empty states per section -->
                    <?php if ($playlists === [] && $subscriptions !== []): ?>
                        <div class="medialib__empty" data-filter="playlist">
                            Нет плейлистов. Нажми «+» чтобы создать первый.
                        </div>
                    <?php elseif ($playlists !== [] && $subscriptions === []): ?>
                        <div class="medialib__empty" data-filter="artist">
                            Нет подписок. Подпишись на исполнителей на их страницах.
                        </div>
                    <?php elseif ($playlists === [] && $subscriptions === []): ?>
                        <div class="medialib__empty">
                            Нажми «+» чтобы создать плейлист, или найди исполнителей и подпишись на них.
                        </div>
                    <?php endif; ?>

                </div><!-- /.medialib__list -->
            </div><!-- /.medialib -->
        </div>
    </main>

    <footer class="dashboard__player" data-player-root>
        <div class="player__now">
            <div class="player__art" data-player-cover></div>
            <div class="player__info">
                <p class="player__title" data-player-title>Трек не выбран</p>
                <p class="player__artist" data-player-artist>Перейди на главную</p>
            </div>
            <button class="player__btn player__btn--heart" type="button" aria-label="В избранное">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                </svg>
            </button>
        </div>
        <div class="player__center">
            <div class="player__controls">
                <button class="player__btn player__btn--shuffle" type="button" title="Перемешать" tabindex="-1">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/>
                        <polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--prev" type="button" data-player-prev aria-label="Предыдущий трек">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="19 20 9 12 19 4 19 20"/><rect x="5" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--play" type="button" data-player-toggle aria-label="Воспроизвести">
                    <svg class="player__icon-play" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    <svg class="player__icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="6" y="4" width="4" height="16" rx="2"/><rect x="14" y="4" width="4" height="16" rx="2"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--next" type="button" data-player-next aria-label="Следующий трек">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 4 15 12 5 20 5 4"/><rect x="17" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="player__btn player__btn--repeat" type="button" title="Повторить" tabindex="-1">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
            </button>
        </div>
    </footer>
</div>

<audio preload="none" data-player-audio></audio>
<script>
window.PLAYER_CONFIG = {
    csrfToken: '<?= \App\Core\Csrf::token() ?>',
    playlist: <?= json_encode($playerPlaylist, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
    fetchUrl: '/player/playlist'
};
</script>
<script src="/assets/js/player.js"></script>
<script>
(function () {
    'use strict';

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ── Filter chips ─────────────────────────── */
    var chips   = document.querySelectorAll('.medialib__chip');
    var rows    = document.querySelectorAll('#js-medialib-list .medialib__row[data-filter]');
    var empties = document.querySelectorAll('#js-medialib-list .medialib__empty[data-filter]');

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var f = chip.dataset.filter;
            chips.forEach(function (c) { c.classList.toggle('medialib__chip--active', c === chip); });
            rows.forEach(function (row) {
                row.hidden = f !== 'all' && row.dataset.filter !== f;
            });
            empties.forEach(function (el) {
                el.hidden = f !== 'all' && el.dataset.filter !== f;
            });
            /* close liked-panel if its card becomes hidden */
            var likedPanel = document.getElementById('js-liked-panel');
            if (likedPanel && !likedPanel.hidden) {
                var likedCard = document.getElementById('js-liked-card');
                if (likedCard && likedCard.hidden) {
                    likedPanel.hidden = true;
                    likedCard.classList.remove('medialib__row--open');
                    if (likedChevron) { likedChevron.style.transform = ''; }
                }
            }
        });
    });

    /* ── Search within library ────────────────── */
    var searchInput = document.getElementById('js-medialib-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.toLowerCase();
            rows.forEach(function (row) {
                if (row.hidden) { row.style.display = ''; return; }
                var name = (row.dataset.name || '').toLowerCase();
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    /* ── + button → show create form ─────────── */
    var plusBtn     = document.getElementById('js-medialib-plus');
    var createForm  = document.getElementById('js-medialib-create-form');
    if (plusBtn && createForm) {
        plusBtn.addEventListener('click', function () {
            createForm.hidden = !createForm.hidden;
            if (!createForm.hidden) {
                var inp = createForm.querySelector('input[name="title"]');
                if (inp) { inp.focus(); }
            }
        });
    }

    /* ── Expand liked panel ───────────────────── */
    var likedCard    = document.getElementById('js-liked-card');
    var likedPanel   = document.getElementById('js-liked-panel');
    var likedChevron = document.getElementById('js-liked-chevron');
    if (likedCard && likedPanel) {
        likedCard.addEventListener('click', function (e) {
            if (e.target.closest('.medialib__dot-menu')) { return; }
            likedPanel.hidden = !likedPanel.hidden;
            likedCard.classList.toggle('medialib__row--open', !likedPanel.hidden);
            if (likedChevron) {
                likedChevron.style.transform = likedPanel.hidden ? '' : 'rotate(180deg)';
            }
        });
    }

    /* ── Unlike (remove liked track) ─────────── */
    document.querySelectorAll('.js-unlike-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var trackId = btn.dataset.trackId;
            fetch('/tracks/like', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body:    new URLSearchParams({ track_id: trackId })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.error && !data.liked) {
                    var row = btn.closest('.liked-row');
                    if (row) {
                        row.style.transition = 'opacity .25s';
                        row.style.opacity    = '0';
                        setTimeout(function () { row.remove(); }, 260);
                    }
                }
            })
            .catch(function () {});
        });
    });

    /* ── Dot-menu toggle ──────────────────────── */
    document.querySelectorAll('.js-dot-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = btn.parentElement.querySelector('.medialib__ctx-menu');
            if (!menu) { return; }
            var isOpen = !menu.hidden;
            /* close all other menus first */
            document.querySelectorAll('.medialib__ctx-menu').forEach(function (m) { m.hidden = true; });
            document.querySelectorAll('.js-dot-btn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
            if (!isOpen) {
                menu.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
    /* close menus on outside click */
    document.addEventListener('click', function () {
        document.querySelectorAll('.medialib__ctx-menu').forEach(function (m) { m.hidden = true; });
        document.querySelectorAll('.js-dot-btn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    });

    /* ── Unsubscribe ──────────────────────────── */
    document.querySelectorAll('.js-unsubscribe').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var authorId = btn.dataset.authorId;
            fetch('/authors/subscribe', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
                body:    new URLSearchParams({ author_id: authorId })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.error && !data.subscribed) {
                    var row = btn.closest('.medialib__row');
                    if (row) {
                        row.style.transition = 'opacity .25s';
                        row.style.opacity    = '0';
                        setTimeout(function () { row.remove(); }, 260);
                    }
                }
            })
            .catch(function () {});
        });
    });
}());
</script>
<script src="/assets/js/search.js"></script>
</body>
</html>
