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
    <title>Подкасты | Richsound</title>
    <script src="/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/assets/css/home.css">
    <link rel="stylesheet" href="/assets/css/player.css">
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
            <a class="sidebar__link" href="/">
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
            <a class="sidebar__link sidebar__link--active" href="/podcasts">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"></path>
                    <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                </svg>
                <span class="sidebar__text">Подкасты</span>
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
                        <path d="M7 8h10"></path><path d="M7 12h10"></path><path d="M7 16h6"></path>
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
                <a class="tabs__item" href="/">Музыка</a>
                <a class="tabs__item tabs__item--active" href="/podcasts">Подкасты</a>
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
                        <span class="topbar__profile-name"><?= $h((string) ($user['name'] ?? '')) ?></span>
                        <span class="topbar__profile-role"><?= $h((string) ($user['email'] ?? '')) ?></span>
                    </div>
                <?php else: ?>
                    <a class="topbar__auth-link" href="/login">Войти</a>
                    <a class="topbar__auth-link topbar__auth-link--primary" href="/register">Регистрация</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="main__content">
            <div class="coming-page">

                <!-- Hero -->
                <div class="coming-hero">
                    <div class="coming-hero__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                            <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"></path>
                            <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                        </svg>
                    </div>
                    <span class="coming-hero__badge">Скоро</span>
                    <h1 class="coming-hero__title">Подкасты</h1>
                    <p class="coming-hero__sub">Авторы Richsound запускают подкасты — многосерийные аудио-шоу о музыке, культуре и творчестве. Подписывайся на любимые шоу и слушай новые выпуски сразу после выхода.</p>
                </div>

                <!-- Features -->
                <div class="coming-feats">
                    <div class="coming-feat">
                        <div class="coming-feat__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"></path>
                                <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                            </svg>
                        </div>
                        <p class="coming-feat__name">Авторские шоу</p>
                        <p class="coming-feat__desc">Любимые авторы платформы ведут подкасты: интервью, разборы треков, истории из индустрии.</p>
                    </div>
                    <div class="coming-feat coming-feat--cyan">
                        <div class="coming-feat__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <p class="coming-feat__name">Подписка на шоу</p>
                        <p class="coming-feat__desc">Подпишись на подкаст и получай уведомление сразу после выхода нового выпуска.</p>
                    </div>
                    <div class="coming-feat">
                        <div class="coming-feat__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <p class="coming-feat__name">Прогресс прослушивания</p>
                        <p class="coming-feat__desc">Плеер запоминает, где ты остановился — продолжай с любого устройства с того же места.</p>
                    </div>
                </div>

                <!-- Ghost preview -->
                <div class="coming-preview">
                    <div class="coming-preview__header">
                        <span class="coming-preview__marker"></span>
                        <span class="coming-preview__label">Предпросмотр интерфейса</span>
                    </div>
                    <div class="coming-preview__grid--square">
                        <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="coming-ghost coming-ghost--square">
                            <div class="coming-ghost__art"></div>
                            <div class="coming-ghost__lines">
                                <div class="coming-ghost__line coming-ghost__line--lg"></div>
                                <div class="coming-ghost__line coming-ghost__line--md"></div>
                                <div class="coming-ghost__line coming-ghost__line--sm"></div>
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
