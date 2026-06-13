<?php

declare(strict_types=1);

$userName = $user['name'] ?? null;
$isAuthenticated = $user !== null;
$featuredTrack = is_array($featuredTrack ?? null) ? $featuredTrack : null;
$heroTracks = is_array($heroTracks ?? null) ? $heroTracks : [];
$latestTracks = is_array($latestTracks ?? null) ? $latestTracks : [];
$popularTracks = is_array($popularTracks ?? null) ? $popularTracks : [];
$roleLabels = [
    'listener' => 'Слушатель',
    'author' => 'Автор',
    'admin' => 'Администратор',
];
$userRole = $isAuthenticated ? ($roleLabels[$user['role'] ?? ''] ?? 'Премиум-аккаунт') : 'Премиум-аккаунт';

$likedTrackIds = is_array($likedTrackIds ?? null) ? array_map('intval', $likedTrackIds) : [];
$userPlaylists = is_array($userPlaylists ?? null) ? $userPlaylists : [];
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDuration = static function (mixed $seconds): string {
    $total = max(0, (int) $seconds);
    $minutes = intdiv($total, 60);
    $remainder = $total % 60;

    return sprintf('%d:%02d', $minutes, $remainder);
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
$coverStyle = static function (?string $path) use ($normalizeMediaUrl, $h): string {
    $url = $normalizeMediaUrl($path);

    if ($url === '') {
        return '';
    }

    return ' style="background-image: linear-gradient(180deg, rgba(0,0,0,.28), rgba(0,0,0,.18)), url(\'' . $h($url) . '\');"';
};

$playlist = [];
$trackIndexById = [];

foreach (array_merge($latestTracks, $popularTracks) as $track) {
    if (!is_array($track)) {
        continue;
    }

    $trackId = (int) ($track['id'] ?? 0);
    $audioUrl = $normalizeMediaUrl((string) ($track['file_path'] ?? ''));

    if ($trackId <= 0 || $audioUrl === '' || isset($trackIndexById[$trackId])) {
        continue;
    }

    $playlist[] = [
        'id' => $trackId,
        'title' => (string) ($track['title'] ?? 'Без названия'),
        'artist' => (string) ($track['author_name'] ?? 'Неизвестный исполнитель'),
        'authorId' => (int) ($track['author_id'] ?? 0),
        'album' => (string) ($track['album_title'] ?? ''),
        'audioUrl' => '/player/stream?id=' . $trackId,
        'coverUrl' => $normalizeMediaUrl((string) ($track['cover_path'] ?? '')),
        'duration' => (int) ($track['duration'] ?? 0),
        'plays' => (int) ($track['plays_count'] ?? 0),
        'likes' => (int) ($track['likes_count'] ?? 0),
    ];
    $trackIndexById[$trackId] = count($playlist) - 1;
}

$initialTrack = $featuredTrack;
if ($initialTrack === null && $playlist !== []) {
    $initialTrack = [
        'title' => $playlist[0]['title'],
        'author_name' => $playlist[0]['artist'],
        'album_title' => $playlist[0]['album'],
        'cover_path' => $playlist[0]['coverUrl'],
        'duration' => $playlist[0]['duration'],
        'id' => $playlist[0]['id'],
        'plays_count' => $playlist[0]['plays'],
        'likes_count' => $playlist[0]['likes'],
    ];
}
$initialIndex = $initialTrack !== null ? ($trackIndexById[(int) ($initialTrack['id'] ?? 0)] ?? 0) : 0;
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richsound</title>
    <script src="/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/assets/css/home.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
</head>
<body class="page">
<div class="dashboard">
    <?php $navActive = 'home'; $brandMeta = $userRole; require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="dashboard__main main">
        <header class="main__topbar topbar">
            <nav class="topbar__tabs tabs" aria-label="Разделы">
                <a class="tabs__item tabs__item--active" href="/">Музыка</a>
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
                        <span class="topbar__profile-role"><?= $h((string) $user['email']) ?></span>
                    </div>
                <?php else: ?>
                    <a class="topbar__auth-link" href="/login" data-turbo="false">Войти</a>
                    <a class="topbar__auth-link topbar__auth-link--primary" href="/register" data-turbo="false">Регистрация</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="main__content">
            <?php if (!empty($success)): ?>
                <div class="alert alert--success"><?= $h($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert--error"><?= $h($error) ?></div>
            <?php endif; ?>

            <section class="hero">
                <article class="hero__banner">
                    <div class="hero__slideshow">
                        <?php if ($heroTracks !== []): ?>
                            <?php foreach ($heroTracks as $slideIdx => $slide): ?>
                            <div class="hero__slide<?= $slideIdx === 0 ? ' hero__slide--active' : '' ?>">
                                <div class="hero__badge">Новый релиз</div>
                                <h1 class="hero__title">
                                    <span class="hero__title-accent"><?= $h($slide['title']) ?></span>
                                </h1>
                                <p class="hero__description">
                                    <?= $h(($slide['author_name'] ?? 'Неизвестный исполнитель') . (!empty($slide['album_title']) ? ' · альбом «' . $slide['album_title'] . '»' : '')) ?>
                                </p>
                                <div class="hero__actions">
                                    <?php if (isset($trackIndexById[(int) $slide['id']])): ?>
                                        <button class="button button--primary js-play-track" type="button" data-track-index="<?= $h($trackIndexById[(int) $slide['id']]) ?>">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M8 5.14v13.72a1 1 0 0 0 1.5.87l10.74-6.86a1 1 0 0 0 0-1.74L9.5 4.27A1 1 0 0 0 8 5.14z"></path>
                                            </svg>
                                            <span>Слушать сейчас</span>
                                        </button>
                                    <?php elseif (!$isAuthenticated): ?>
                                        <a class="button button--primary" href="/register" data-turbo="false">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M8 5.14v13.72a1 1 0 0 0 1.5.87l10.74-6.86a1 1 0 0 0 0-1.74L9.5 4.27A1 1 0 0 0 8 5.14z"></path>
                                            </svg>
                                            <span>Создать аккаунт</span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($slide['author_name'])): ?>
                                        <button class="button button--secondary" type="button" disabled>
                                            <?= $h('Исполнитель: ' . $slide['author_name']) ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="hero__slide hero__slide--active">
                                <div class="hero__badge">Новый релиз</div>
                                <h1 class="hero__title"><span class="hero__title-accent">Музыка уже ждёт</span></h1>
                                <p class="hero__description">На платформе пока нет загруженных треков. Добавь первые записи через админку или кабинет автора.</p>
                                <div class="hero__actions">
                                    <a class="button button--secondary" href="<?= $isAuthenticated ? '/admin' : '/login' ?>">
                                        <?= $isAuthenticated ? 'Открыть каталог' : 'Войти' ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </article>

                <aside class="hero__mix mix-card">
                    <div class="mix-card__badge" aria-hidden="true">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"></path>
                            <path d="m18 4 .7 2 .7-2L21 3.3 19.4 2.7 18.7 1l-.7 1.7L16.3 3.3z"></path>
                            <path d="m18 18 .7 2 .7-2 1.6-.7-1.6-.6-.7-1.7-.7 1.7-1.7.6z"></path>
                        </svg>
                    </div>
                    <h2 class="mix-card__title"><?= $h($playlist !== [] ? 'Доступно треков: ' . count($playlist) : 'Пустая библиотека') ?></h2>
                    <p class="mix-card__description">
                        <?php if ($playlist !== []): ?>
                            <?= $h('Популярных треков: ' . count($popularTracks) . '. Свежих релизов: ' . count($latestTracks) . '.') ?>
                        <?php else: ?>
                            После загрузки треков здесь появятся карточки, подборки и рабочий плеер на всей главной.
                        <?php endif; ?>
                    </p>
                    <?php if ($playlist !== []): ?>
                        <button class="button button--secondary js-play-track" type="button" data-track-index="0">Запустить плеер</button>
                    <?php else: ?>
                        <a class="button button--secondary" href="<?= $isAuthenticated ? '/admin' : '/register' ?>">
                            <?= $isAuthenticated ? 'Открыть админку' : 'Присоединиться к Richsound' ?>
                        </a>
                    <?php endif; ?>
                </aside>
            </section>

            <?php if (!$isAuthenticated): ?>
            <section class="roles-section">
                <div class="section__header">
                    <span class="section__marker"></span>
                    <h2 class="section__title">Твой аккаунт на Richsound</h2>
                    <span class="section__link">Сейчас: Гость</span>
                </div>
                <div class="roles-grid">

                    <div class="role-card role-card--guest">
                        <div class="role-card__badge role-card__badge--muted">Вы здесь</div>
                        <div class="role-card__icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                            </svg>
                        </div>
                        <h3 class="role-card__name">Гость</h3>
                        <p class="role-card__desc">Просматривай главную, страницы треков и авторов без регистрации</p>
                        <ul class="role-card__perks">
                            <li class="perk perk--ok">Главная страница</li>
                            <li class="perk perk--ok">Страницы треков и авторов</li>
                            <li class="perk perk--ok">Поиск по каталогу</li>
                            <li class="perk perk--no">Прослушивание треков</li>
                            <li class="perk perk--no">Лайки и плейлисты</li>
                        </ul>
                        <a class="role-card__cta" href="/login" data-turbo="false">Войти в аккаунт</a>
                    </div>

                    <div class="role-card role-card--listener">
                        <div class="role-card__icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                            </svg>
                        </div>
                        <h3 class="role-card__name">Слушатель</h3>
                        <p class="role-card__desc">Слушай треки, создавай плейлисты и подписывайся на любимых авторов</p>
                        <ul class="role-card__perks">
                            <li class="perk perk--ok">Всё что у гостя</li>
                            <li class="perk perk--ok">Прослушивание треков</li>
                            <li class="perk perk--ok">Лайки и история</li>
                            <li class="perk perk--ok">Плейлисты и библиотека</li>
                            <li class="perk perk--ok">Подписки на авторов</li>
                        </ul>
                        <a class="role-card__cta" href="/register" data-turbo="false">Зарегистрироваться</a>
                    </div>

                    <div class="role-card role-card--author">
                        <div class="role-card__badge role-card__badge--accent">Для музыкантов</div>
                        <div class="role-card__icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a3 3 0 0 1 3 3v7a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <path d="M12 19v3"/><path d="M8 22h8"/>
                            </svg>
                        </div>
                        <h3 class="role-card__name">Автор</h3>
                        <p class="role-card__desc">Публикуй треки и альбомы, следи за аналитикой и расти как артист</p>
                        <ul class="role-card__perks">
                            <li class="perk perk--ok">Всё что у слушателя</li>
                            <li class="perk perk--ok">Загрузка треков и альбомов</li>
                            <li class="perk perk--ok">Кабинет автора</li>
                            <li class="perk perk--ok">Аналитика прослушиваний</li>
                            <li class="perk perk--ok">Публичный профиль артиста</li>
                        </ul>
                        <a class="role-card__cta role-card__cta--accent" href="/register" data-turbo="false">Стать автором</a>
                    </div>

                    <div class="role-card role-card--admin">
                        <div class="role-card__icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M7 8h10"/><path d="M7 12h10"/><path d="M7 16h6"/>
                            </svg>
                        </div>
                        <h3 class="role-card__name">Администратор</h3>
                        <p class="role-card__desc">Полный контроль над платформой, пользователями и контентом</p>
                        <ul class="role-card__perks">
                            <li class="perk perk--ok">Всё что у автора</li>
                            <li class="perk perk--ok">Управление пользователями</li>
                            <li class="perk perk--ok">Управление контентом</li>
                            <li class="perk perk--ok">Статистика платформы</li>
                            <li class="perk perk--ok">Доступ к админ-панели</li>
                        </ul>
                        <span class="role-card__note">Назначается администратором платформы</span>
                    </div>

                </div>
            </section>
            <?php endif; ?>

            <section class="section">
                <div class="section__header">
                    <span class="section__marker"></span>
                    <h2 class="section__title">Свежие треки</h2>
                    <span class="section__link"><?= $h((int) ($totalTracks ?? count($latestTracks))) ?> шт.</span>
                </div>

                <?php if ($latestTracks !== []): ?>
                    <div class="section__albums album-grid">
                        <?php foreach ($latestTracks as $track): ?>
                            <?php
                                $index   = $trackIndexById[(int) $track['id']] ?? null;
                                $trackId = (int) $track['id'];
                                $isLiked = in_array($trackId, $likedTrackIds, true);
                            ?>
                            <article class="album-card<?= $index !== null ? ' js-play-track' : '' ?>"<?= $index !== null ? ' data-track-index="' . $h((string) $index) . '" role="button" tabindex="0"' : '' ?>>
                                <div class="album-card__art album-card__art--media"<?= $coverStyle((string) ($track['cover_path'] ?? '')) ?>>
                                    <button class="album-card__play" type="button" aria-label="Воспроизвести <?= $h($track['title']) ?>"<?= $index === null ? ' disabled' : '' ?>>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M8 5.14v13.72a1 1 0 0 0 1.5.87l10.74-6.86a1 1 0 0 0 0-1.74L9.5 4.27A1 1 0 0 0 8 5.14z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="album-card__meta">
                                    <h3 class="album-card__title"><a href="/tracks/<?= $h($trackId) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;" tabindex="-1"><?= $h($track['title']) ?></a></h3>
                                    <p class="album-card__artist"><a href="/authors/<?= $h((int)($track['author_id'] ?? 0)) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;"><?= $h($track['author_name'] ?? 'Неизвестный исполнитель') ?></a></p>
                                    <p class="album-card__stats"><?= $h($formatDuration($track['duration'] ?? 0)) ?> · <?= $h((int) ($track['plays_count'] ?? 0)) ?> просл.</p>
                                    <div class="track-actions" onclick="event.stopPropagation()">
                                        <?php if ($isAuthenticated): ?>
                                            <button type="button"
                                                    class="track-actions__like js-like-btn<?= $isLiked ? ' track-actions__like--active' : '' ?>"
                                                    data-track-id="<?= $h($trackId) ?>"
                                                    aria-label="<?= $isLiked ? 'Убрать лайк' : 'Поставить лайк' ?>">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $isLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="js-like-icon">
                                                    <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"></path>
                                                </svg>
                                                <span class="js-like-count"><?= $h((int) ($track['likes_count'] ?? 0)) ?></span>
                                            </button>
                                            <?php if ($userPlaylists !== []): ?>
                                                <div class="track-actions__playlist-wrap">
                                                    <button type="button" class="track-actions__add js-playlist-btn" data-track-id="<?= $h($trackId) ?>" aria-label="Добавить в плейлист">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                                                    </button>
                                                    <ul class="playlist-dropdown" hidden>
                                                        <?php foreach ($userPlaylists as $pl): ?>
                                                            <li><button type="button" class="js-add-to-playlist" data-playlist-id="<?= $h($pl['id']) ?>" data-track-id="<?= $h($trackId) ?>"><?= $h($pl['title']) ?></button></li>
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
                <?php else: ?>
                    <div class="empty-state">На главной пока нечего показывать. Добавь треки в каталог.</div>
                <?php endif; ?>

                <?php if (($totalPages ?? 1) > 1): ?>
                    <nav class="pagination" aria-label="Навигация по страницам">
                        <?php if (($page ?? 1) > 1): ?>
                            <a class="pagination__btn" href="/?page=<?= (int) $page - 1 ?>">← Назад</a>
                        <?php endif; ?>
                        <span class="pagination__info">Страница <?= (int) $page ?> из <?= (int) $totalPages ?></span>
                        <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
                            <a class="pagination__btn" href="/?page=<?= (int) $page + 1 ?>">Вперёд →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>

            <section class="section">
                <div class="section__header">
                    <span class="section__marker"></span>
                    <h2 class="section__title">Популярно сейчас</h2>
                    <span class="section__link"><?= $h(count($popularTracks)) ?> треков</span>
                </div>

                <?php if ($popularTracks !== []): ?>
                    <div class="track-grid">
                        <?php foreach ($popularTracks as $track): ?>
                            <?php
                                $index   = $trackIndexById[(int) $track['id']] ?? null;
                                $trackId = (int) $track['id'];
                                $isLiked = in_array($trackId, $likedTrackIds, true);
                            ?>
                            <article class="track-card<?= $index !== null ? ' js-play-track' : '' ?>"<?= $index !== null ? ' data-track-index="' . $h((string) $index) . '" role="button" tabindex="0"' : '' ?>>
                                <div class="track-card__cover"<?= $coverStyle((string) ($track['cover_path'] ?? '')) ?>></div>
                                <div>
                                    <h3 class="track-card__title"><a href="/tracks/<?= $h($trackId) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;" tabindex="-1"><?= $h($track['title']) ?></a></h3>
                                    <p class="track-card__artist"><a href="/authors/<?= $h((int)($track['author_id'] ?? 0)) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;"><?= $h($track['author_name'] ?? 'Неизвестный исполнитель') ?></a></p>
                                </div>
                                <div class="track-card__meta" onclick="event.stopPropagation()">
                                    <button class="track-card__play" type="button"<?= $index === null ? ' disabled' : '' ?>>Слушать</button>
                                    <span><?= $h($formatDuration($track['duration'] ?? 0)) ?></span>
                                    <span><?= $h((int) ($track['listens_count'] ?? 0)) ?> просл.</span>
                                    <?php if ($isAuthenticated): ?>
                                        <div class="track-actions track-actions--inline">
                                            <button type="button"
                                                    class="track-actions__like js-like-btn<?= $isLiked ? ' track-actions__like--active' : '' ?>"
                                                    data-track-id="<?= $h($trackId) ?>"
                                                    aria-label="<?= $isLiked ? 'Убрать лайк' : 'Поставить лайк' ?>">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $isLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="js-like-icon">
                                                    <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 7c0 3.87-3.4 7-8.55 11.68z"></path>
                                                </svg>
                                                <span class="js-like-count"><?= $h((int) ($track['likes_count'] ?? 0)) ?></span>
                                            </button>
                                            <?php if ($userPlaylists !== []): ?>
                                                <div class="track-actions__playlist-wrap">
                                                    <button type="button" class="track-actions__add js-playlist-btn" data-track-id="<?= $h($trackId) ?>" aria-label="Добавить в плейлист">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                                                    </button>
                                                    <ul class="playlist-dropdown" hidden>
                                                        <?php foreach ($userPlaylists as $pl): ?>
                                                            <li><button type="button" class="js-add-to-playlist" data-playlist-id="<?= $h($pl['id']) ?>" data-track-id="<?= $h($trackId) ?>"><?= $h($pl['title']) ?></button></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="track-actions__likes-count"><?= $h((int) ($track['likes_count'] ?? 0)) ?> ♥</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Популярные треки появятся после первых прослушиваний.</div>
                <?php endif; ?>
            </section>
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

    /* ── Likes ───────────────────────────────────── */
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
            /* close all other dropdowns */
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

    /* close dropdowns on outside click */
    if (!window.__rsDropdownDocBound) {
        window.__rsDropdownDocBound = true;
        document.addEventListener('click', function () {
            document.querySelectorAll('.playlist-dropdown').forEach(function (d) { d.hidden = true; });
        });
    }
}());
</script>
<script>
(function () {
    var slides = document.querySelectorAll('.hero__slide');
    if (slides.length <= 1) { return; }

    var current = 0;

    function goTo(idx) {
        slides[current].classList.remove('hero__slide--active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('hero__slide--active');
    }

    /* This script re-runs on every Turbo visit — stop the previous slider
       interval, it holds slides from the discarded body. */
    if (window.__rsHeroSlider) { clearInterval(window.__rsHeroSlider); }
    window.__rsHeroSlider = setInterval(function () { goTo(current + 1); }, 8000);
}());
</script>

</body>
</html>
