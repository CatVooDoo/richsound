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
    <aside class="dashboard__sidebar sidebar">
        <div class="sidebar__brand brand">
            <div class="brand__title">Richsound</div>
            <div class="brand__meta"><?= $h($userRole) ?></div>
        </div>

        <nav class="sidebar__nav" aria-label="Основная навигация">
            <a class="sidebar__link sidebar__link--active" href="/">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5"></path>
                    <path d="M5 9.5V21h14V9.5"></path>
                    <path d="M9 21v-6h6v6"></path>
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
            <a class="sidebar__link" href="/library">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m16 6 4 14"></path>
                    <path d="M12 6v14"></path>
                    <path d="M8 8v12"></path>
                    <path d="M4 4v16"></path>
                </svg>
                <span class="sidebar__text">Моя медиатека</span>
            </a>
            <a class="sidebar__link sidebar__link--create" href="/library">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"></path>
                </svg>
                <span class="sidebar__text">Создать</span>
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
            <div class="sidebar__label">Управление</div>
            <a class="sidebar__link" href="/library">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                </svg>
                <span class="sidebar__text">Создать плейлист</span>
            </a>
            <a class="sidebar__link" href="/library">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 20-1.45-1.32C5.4 14 2 10.87 2 7a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 7c0 3.87-3.4 7-8.55 11.68z"></path>
                </svg>
                <span class="sidebar__text">Любимые треки</span>
            </a>
            <?php if (\in_array(($user['role'] ?? null), ['author', 'admin'], true)): ?>
                <a class="sidebar__link" href="/author">
                    <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2a3 3 0 0 1 3 3v7a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <path d="M12 19v3"></path>
                        <path d="M8 22h8"></path>
                    </svg>
                    <span class="sidebar__text">Кабинет автора</span>
                </a>
            <?php endif; ?>
            <?php if (($user['role'] ?? null) === 'admin'): ?>
                <a class="sidebar__link" href="/admin">
                    <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                        <path d="M7 8h10"></path>
                        <path d="M7 12h10"></path>
                        <path d="M7 16h6"></path>
                    </svg>
                    <span class="sidebar__text">Админка</span>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($isAuthenticated): ?>
            <form action="/logout" method="post">
                <?= \App\Core\Csrf::field() ?>
                <button class="sidebar__upgrade" type="submit">Выйти</button>
            </form>
        <?php else: ?>
            <a class="sidebar__upgrade sidebar__upgrade--link" href="/register">Создать аккаунт</a>
        <?php endif; ?>
    </aside>

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
                    <a class="topbar__auth-link" href="/login">Войти</a>
                    <a class="topbar__auth-link topbar__auth-link--primary" href="/register">Регистрация</a>
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
                                        <a class="button button--primary" href="/register">
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
                        <a class="role-card__cta" href="/login">Войти в аккаунт</a>
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
                        <a class="role-card__cta" href="/register">Зарегистрироваться</a>
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
                        <a class="role-card__cta role-card__cta--accent" href="/register">Стать автором</a>
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

    <footer class="dashboard__player" data-player-root>

        <!-- ① Now playing -->
        <div class="player__now">
            <div class="player__art" data-player-cover<?= $coverStyle((string) ($initialTrack['cover_path'] ?? $initialTrack['coverUrl'] ?? '')) ?>></div>
            <div class="player__info">
                <p class="player__title" data-player-title><?= $h($initialTrack['title'] ?? 'Трек не выбран') ?></p>
                <p class="player__artist" data-player-artist><?= $h($initialTrack['author_name'] ?? $initialTrack['artist'] ?? 'Выбери трек') ?></p>
            </div>
            <button class="player__btn player__btn--heart" type="button" aria-label="В избранное">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                </svg>
            </button>
        </div>

        <!-- ② Controls + progress -->
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
                <span class="player__time" data-player-duration><?= $h($formatDuration($initialTrack['duration'] ?? 0)) ?></span>
            </div>
        </div>

        <!-- ③ Volume -->
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

<!-- ── Mobile full-screen player ─────────────────────────────── -->
<div class="mobile-player" id="mobilePlayer" role="dialog" aria-modal="true" aria-label="Полноэкранный плеер" aria-hidden="true">

    <!-- Blurred album art background (set via JS: style="background-image:url(...)") -->
    <div class="mplayer__bg" data-mplayer-bg></div>
    <!-- Dark gradient overlay on top of blur -->
    <div class="mplayer__overlay"></div>

    <div class="mplayer__content">

        <!-- ── Main view (fills one screen, then queue on scroll) ── -->
        <div class="mplayer__main">

            <!-- Header row -->
            <div class="mplayer__header">
                <button class="mplayer__icon-btn mplayer__close" id="mobilePlayerClose" aria-label="Свернуть">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
                <span class="mplayer__header-label">Сейчас играет</span>
                <button class="mplayer__icon-btn mplayer__more" aria-label="Ещё">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                    </svg>
                </button>
            </div>

            <!-- Cover art -->
            <div class="mplayer__art-wrap">
                <div class="mplayer__art" data-mplayer-cover></div>
            </div>

            <!-- Track info + heart -->
            <div class="mplayer__meta">
                <div class="mplayer__info">
                    <p class="mplayer__title" data-mplayer-title>Трек не выбран</p>
                    <p class="mplayer__artist" data-mplayer-artist>—</p>
                </div>
                <button class="mplayer__icon-btn mplayer__heart" aria-label="В избранное" data-mplayer-heart>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m12 21-1.45-1.32C5.4 15 2 11.86 2 8a5 5 0 0 1 9.08-2.92A5 5 0 0 1 20 8c0 3.86-3.4 7-8.55 11.68z"/>
                    </svg>
                </button>
            </div>

            <!-- Progress bar -->
            <div class="mplayer__progress">
                <div class="mplayer__slider">
                    <div class="mplayer__slider-rail"></div>
                    <div class="mplayer__slider-fill" data-mplayer-fill style="width:0%"></div>
                    <input class="mplayer__range" type="range" min="0" max="100" value="0" step="0.1"
                           data-mplayer-seek aria-label="Позиция воспроизведения">
                </div>
                <div class="mplayer__times">
                    <span data-mplayer-current>0:00</span>
                    <span data-mplayer-duration>0:00</span>
                </div>
            </div>

            <!-- Controls: shuffle · prev · play · next · repeat -->
            <div class="mplayer__controls">
                <button class="mplayer__icon-btn" aria-label="Перемешать" data-mplayer-shuffle>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/>
                        <polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/>
                    </svg>
                </button>
                <button class="mplayer__icon-btn mplayer__skip" aria-label="Предыдущий" data-mplayer-prev>
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="19 20 9 12 19 4 19 20"/><rect x="5" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="mplayer__play-btn" aria-label="Воспроизвести / Пауза" data-mplayer-toggle>
                    <svg class="mplayer__icon-play" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    <svg class="mplayer__icon-pause" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="6" y="4" width="4" height="16" rx="2"/><rect x="14" y="4" width="4" height="16" rx="2"/>
                    </svg>
                </button>
                <button class="mplayer__icon-btn mplayer__skip" aria-label="Следующий" data-mplayer-next>
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="5 4 15 12 5 20 5 4"/><rect x="17" y="4" width="2" height="16" rx="1"/>
                    </svg>
                </button>
                <button class="mplayer__icon-btn" aria-label="Повтор" data-mplayer-repeat>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
                </button>
            </div>

            <!-- Volume -->
            <div class="mplayer__volume">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                </svg>
                <div class="mplayer__vol-track">
                    <div class="mplayer__vol-rail"></div>
                    <div class="mplayer__vol-fill" data-mplayer-vol-fill style="width:80%"></div>
                    <input class="mplayer__range" type="range" min="0" max="1" step="0.01" value="0.8"
                           data-mplayer-volume aria-label="Громкость">
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                    <path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                </svg>
            </div>

        </div><!-- /.mplayer__main -->

        <!-- ── Queue (revealed on scroll) ──────────────────── -->
        <div class="mplayer__queue">
            <div class="mplayer__queue-header">
                <span class="mplayer__queue-title-label">Далее в очереди</span>
            </div>
            <!-- Items populated by JS (data-mplayer-queue-list) -->
            <div class="mplayer__queue-list" data-mplayer-queue-list>
                <!-- Static placeholders — replaced by JS when wired -->
                <div class="mplayer__queue-item mplayer__queue-item--skeleton">
                    <div class="mplayer__queue-cover"></div>
                    <div class="mplayer__queue-info">
                        <p class="mplayer__queue-track">Название трека</p>
                        <p class="mplayer__queue-artist">Исполнитель</p>
                    </div>
                    <span class="mplayer__queue-dur">3:42</span>
                </div>
                <div class="mplayer__queue-item mplayer__queue-item--skeleton">
                    <div class="mplayer__queue-cover"></div>
                    <div class="mplayer__queue-info">
                        <p class="mplayer__queue-track">Название трека</p>
                        <p class="mplayer__queue-artist">Исполнитель</p>
                    </div>
                    <span class="mplayer__queue-dur">4:11</span>
                </div>
                <div class="mplayer__queue-item mplayer__queue-item--skeleton">
                    <div class="mplayer__queue-cover"></div>
                    <div class="mplayer__queue-info">
                        <p class="mplayer__queue-track">Название трека</p>
                        <p class="mplayer__queue-artist">Исполнитель</p>
                    </div>
                    <span class="mplayer__queue-dur">2:58</span>
                </div>
                <div class="mplayer__queue-item mplayer__queue-item--skeleton">
                    <div class="mplayer__queue-cover"></div>
                    <div class="mplayer__queue-info">
                        <p class="mplayer__queue-track">Название трека</p>
                        <p class="mplayer__queue-artist">Исполнитель</p>
                    </div>
                    <span class="mplayer__queue-dur">5:03</span>
                </div>
            </div>
        </div>

    </div><!-- /.mplayer__content -->
</div><!-- /#mobilePlayer -->

<audio preload="none" data-player-audio></audio>
<script>
window.PLAYER_CONFIG = {
    csrfToken:    '<?= \App\Core\Csrf::token() ?>',
    playlist:     <?= json_encode($playlist, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    initialIndex: <?= (int) $initialIndex ?>
};
</script>
<script src="/assets/js/player.js"></script>
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
    document.addEventListener('click', function () {
        document.querySelectorAll('.playlist-dropdown').forEach(function (d) { d.hidden = true; });
    });
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

    setInterval(function () { goTo(current + 1); }, 8000);
}());
</script>

<script src="/assets/js/mobile-player.js"></script>
</body>
</html>
