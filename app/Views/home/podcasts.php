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
    <?php $navActive = 'podcasts'; $brandMeta = $userRole; require __DIR__ . '/../partials/sidebar.php'; ?>

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
