<?php

declare(strict_types=1);

$user            = is_array($user ?? null) ? $user : null;
$isAuthenticated = $user !== null;
$roleLabels      = ['listener' => 'Слушатель', 'author' => 'Автор', 'admin' => 'Администратор'];
$userRole        = $isAuthenticated ? ($roleLabels[$user['role'] ?? ''] ?? 'Аккаунт') : 'Гость';
$h               = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Эфиры | Richsound</title>
    <script src="/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
</head>
<body class="page">
<div class="dashboard">
    <?php $navActive = 'broadcasts'; $brandMeta = $userRole; require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="dashboard__main main">
        <header class="main__topbar topbar">
            <nav class="topbar__tabs tabs" aria-label="Разделы">
                <a class="tabs__item" href="/">Музыка</a>
                <a class="tabs__item" href="/podcasts">Подкасты</a>
                <a class="tabs__item tabs__item--active" href="/broadcasts">Эфиры</a>
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
            <div class="coming-page">

                <!-- Hero -->
                <div class="coming-hero">
                    <div class="coming-hero__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                            <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                            <circle cx="12" cy="20" r="1" fill="currentColor"></circle>
                        </svg>
                    </div>
                    <span class="coming-hero__badge">Скоро</span>
                    <h1 class="coming-hero__title">Эфиры</h1>
                    <p class="coming-hero__sub">Авторы Richsound выходят в прямой эфир — живые концерты, Q&amp;A сессии, прослушивания новых треков. Общайся в чате, задавай вопросы и поддерживай исполнителей в реальном времени.</p>
                </div>

                <!-- Features -->
                <div class="coming-feats">
                    <div class="coming-feat coming-feat--cyan">
                        <div class="coming-feat__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"></circle>
                            </svg>
                        </div>
                        <p class="coming-feat__name">Живой эфир</p>
                        <p class="coming-feat__desc">Авторы выходят в прямой эфир — концерты, студийные сессии и разговоры с аудиторией.</p>
                    </div>
                    <div class="coming-feat">
                        <div class="coming-feat__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <p class="coming-feat__name">Чат в реальном времени</p>
                        <p class="coming-feat__desc">Общайся с другими слушателями и задавай вопросы автору прямо во время эфира.</p>
                    </div>
                    <div class="coming-feat coming-feat--cyan">
                        <div class="coming-feat__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <p class="coming-feat__name">Напоминания об эфирах</p>
                        <p class="coming-feat__desc">Подпишись на автора и получай уведомление за несколько минут до начала его прямого эфира.</p>
                    </div>
                </div>

                <!-- Ghost preview -->
                <div class="coming-preview">
                    <div class="coming-preview__header">
                        <span class="coming-preview__marker"></span>
                        <span class="coming-preview__label">Предпросмотр интерфейса</span>
                    </div>
                    <div class="coming-preview__grid--wide">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="coming-ghost coming-ghost--wide">
                            <div class="coming-ghost__thumb">
                                <span class="coming-ghost__live-pill">Live</span>
                            </div>
                            <div class="coming-ghost__body">
                                <div class="coming-ghost__viewers">
                                    <div class="coming-ghost__viewers-dot"></div>
                                    <div class="coming-ghost__viewers-text"></div>
                                </div>
                                <div class="coming-ghost__lines">
                                    <div class="coming-ghost__line coming-ghost__line--lg"></div>
                                    <div class="coming-ghost__line coming-ghost__line--md"></div>
                                    <div class="coming-ghost__line coming-ghost__line--sm"></div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

<?php require __DIR__ . '/../partials/player-bar.php'; ?>
</div>

<?php require __DIR__ . '/../partials/mobile-player.php'; ?>

<?php $playerFetchUrl = '/player/playlist'; ?>
<?php require __DIR__ . '/../partials/player-config.php'; ?>
<script src="/assets/js/search.js"></script>
</body>
</html>
