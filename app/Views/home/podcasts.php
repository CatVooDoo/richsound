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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </button>
        </div>
    </footer>
</div>

<audio preload="none" data-player-audio></audio>
<script>
window.PLAYER_CONFIG = {
    csrfToken: '<?= \App\Core\Csrf::token() ?>',
    playlist:  [],
    fetchUrl:  '/player/playlist'
};
</script>
<script src="/assets/js/player.js"></script>
<script src="/assets/js/search.js"></script>
</body>
</html>
