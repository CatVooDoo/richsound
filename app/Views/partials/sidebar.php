<?php

declare(strict_types=1);

/* Shared left navigation sidebar (.dashboard__sidebar).
   Optional context from the including view:
     $navActive — which link to highlight: home|search|library|podcasts|broadcasts
                  (anything else / unset → no active link, e.g. artist profile).
     $brandMeta — text under the logo. Defaults to the role label / «Гость».
     $user      — current user array or null; drives the role-based links
                  (Кабинет автора / Админка) and the logout vs «Создать аккаунт» block. */

$sbUser   = is_array($user ?? null) && $user !== [] ? $user : null;
$sbRole   = $sbUser['role'] ?? null;
$sbAuthed = $sbUser !== null;
$sbActive = (string) ($navActive ?? '');

$sbH = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$sbRoleLabels = ['listener' => 'Слушатель', 'author' => 'Автор', 'admin' => 'Администратор'];
$sbBrandMeta  = $brandMeta ?? ($sbAuthed ? ($sbRoleLabels[$sbRole] ?? 'Аккаунт') : 'Гость');

$sbLink = static function (string $key) use ($sbActive): string {
    return 'sidebar__link' . ($sbActive === $key ? ' sidebar__link--active' : '');
};
?>
    <aside class="dashboard__sidebar sidebar">
        <div class="sidebar__brand brand">
            <div class="brand__title">Richsound</div>
            <div class="brand__meta"><?= $sbH($sbBrandMeta) ?></div>
        </div>

        <nav class="sidebar__nav" aria-label="Основная навигация">
            <a class="<?= $sbLink('home') ?>" href="/">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5"></path>
                    <path d="M5 9.5V21h14V9.5"></path>
                    <path d="M9 21v-6h6v6"></path>
                </svg>
                <span class="sidebar__text">Главная</span>
            </a>
            <a class="<?= $sbLink('search') ?>" href="/search">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <span class="sidebar__text">Поиск</span>
            </a>
            <a class="<?= $sbLink('library') ?>" href="/library">
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
            <a class="<?= $sbLink('podcasts') ?>" href="/podcasts">
                <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"></path>
                    <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                </svg>
                <span class="sidebar__text">Подкасты</span>
                <span class="sidebar__soon">Скоро</span>
            </a>
            <a class="<?= $sbLink('broadcasts') ?>" href="/broadcasts">
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
            <?php if (\in_array($sbRole, ['author', 'admin'], true)): ?>
                <a class="sidebar__link" href="/author" data-turbo="false">
                    <svg class="sidebar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2a3 3 0 0 1 3 3v7a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <path d="M12 19v3"></path>
                        <path d="M8 22h8"></path>
                    </svg>
                    <span class="sidebar__text">Кабинет автора</span>
                </a>
            <?php endif; ?>
            <?php if ($sbRole === 'admin'): ?>
                <a class="sidebar__link" href="/admin" data-turbo="false">
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

        <?php if ($sbAuthed): ?>
            <form action="/logout" method="post">
                <?= \App\Core\Csrf::field() ?>
                <button class="sidebar__upgrade" type="submit">Выйти</button>
            </form>
        <?php else: ?>
            <a class="sidebar__upgrade sidebar__upgrade--link" href="/register" data-turbo="false">Создать аккаунт</a>
        <?php endif; ?>
    </aside>
