<?php

declare(strict_types=1);

$author = is_array($author ?? null) ? $author : [];
$tracks = is_array($tracks ?? null) ? $tracks : [];
$albums = is_array($albums ?? null) ? $albums : [];
$albumOptions = is_array($albumOptions ?? null) ? $albumOptions : [];
$totalPlays      = (int) ($totalPlays ?? 0);
$totalListens    = (int) ($totalListens ?? 0);
$uniqueListeners = (int) ($uniqueListeners ?? 0);
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDuration = static function (mixed $seconds): string {
    $total = max(0, (int) $seconds);

    return sprintf('%d:%02d', intdiv($total, 60), $total % 60);
};
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет автора | Richsound</title>
    <link rel="stylesheet" href="/assets/css/author.css">
    <link rel="stylesheet" href="/assets/css/player.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
</head>
<body class="author-page">
<div class="author-shell">

    <header class="author-header">
        <div>
            <p class="author-header__eyebrow">Кабинет автора</p>
            <h1><?= $h($author['name'] ?? 'Автор') ?></h1>
            <p class="author-header__meta"><?= $h($author['email'] ?? '') ?></p>
        </div>
        <div class="author-header__actions">
            <a class="author-button author-button--ghost" href="/">На главную</a>
            <form action="/logout" method="post">
                <?= \App\Core\Csrf::field() ?>
                <button class="author-button" type="submit">Выйти</button>
            </form>
        </div>
    </header>

    <?php if (!empty($success)): ?>
        <div class="author-alert author-alert--success"><?= $h($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="author-alert author-alert--error"><?= $h($error) ?></div>
    <?php endif; ?>

    <section class="author-summary">
        <article class="author-summary-card">
            <span class="author-summary-card__label">Треков</span>
            <strong class="author-summary-card__value"><?= $h(count($tracks)) ?></strong>
        </article>
        <article class="author-summary-card">
            <span class="author-summary-card__label">Альбомов</span>
            <strong class="author-summary-card__value"><?= $h(count($albums)) ?></strong>
        </article>
        <article class="author-summary-card">
            <span class="author-summary-card__label">Воспроизведений</span>
            <strong class="author-summary-card__value"><?= $h($totalPlays) ?></strong>
        </article>
        <article class="author-summary-card">
            <span class="author-summary-card__label">Прослушиваний</span>
            <strong class="author-summary-card__value"><?= $h($totalListens) ?></strong>
        </article>
        <article class="author-summary-card">
            <span class="author-summary-card__label">Уник. слушателей</span>
            <strong class="author-summary-card__value"><?= $h($uniqueListeners) ?></strong>
        </article>
    </section>

    <nav class="author-tabs" role="tablist">
        <a class="author-tabs__item author-tabs__item--active" href="#overview" role="tab">Обзор</a>
        <a class="author-tabs__item" href="#tracks" role="tab">Мои треки</a>
        <a class="author-tabs__item" href="#albums" role="tab">Мои альбомы</a>
        <a class="author-tabs__item" href="#analytics" role="tab">Аналитика</a>
        <a class="author-tabs__item" href="#profile" role="tab">Профиль</a>
    </nav>

    <!-- Обзор -->
    <section class="author-panel" id="overview">
        <div class="author-panel__header">
            <h2>Обзор</h2>
            <span>Последние треки и альбомы</span>
        </div>

        <?php if ($tracks !== []): ?>
            <p class="author-section-label">Последние треки</p>
            <div class="author-tracks-list" style="margin-bottom: 28px;">
                <?php foreach (array_slice($tracks, 0, 5) as $track): ?>
                    <div class="author-track-item">
                        <div class="author-track-item__cover" <?= !empty($track['cover_path']) ? 'style="background-image: url(\'' . $h((string) $track['cover_path']) . '\')"' : '' ?>></div>
                        <div class="author-track-item__info">
                            <p class="author-track-item__title"><?= $h($track['title']) ?></p>
                            <div class="author-track-item__meta">
                                <span><?= $h((string) ($track['album_title'] ?: 'Без альбома')) ?></span>
                                <span><?= $h($formatDuration($track['duration'])) ?></span>
                                <span>Воспроизведений: <?= $h($track['plays_count']) ?></span>
                                <span>Лайков: <?= $h($track['likes_count']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($albums !== []): ?>
            <p class="author-section-label">Последние альбомы</p>
            <div class="author-albums-list">
                <?php foreach (array_slice($albums, 0, 3) as $album): ?>
                    <div class="author-album-item">
                        <div class="author-album-item__cover" <?= !empty($album['cover_path']) ? 'style="background-image: url(\'' . $h((string) $album['cover_path']) . '\')"' : '' ?>></div>
                        <div class="author-album-item__info">
                            <p class="author-album-item__title"><?= $h($album['title']) ?></p>
                            <div class="author-album-item__meta">
                                <span>Треков: <?= $h($album['tracks_count']) ?></span>
                                <?php if (!empty($album['released_at'])): ?>
                                    <span>Релиз: <?= $h($album['released_at']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($tracks === [] && $albums === []): ?>
            <div class="author-stub">
                <div class="author-stub__icon">🎵</div>
                <h3 class="author-stub__title">Пока здесь пусто</h3>
                <p class="author-stub__desc">Ты ещё не загрузил ни одного трека. Перейди во вкладку «Мои треки», чтобы добавить первый.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Мои треки -->
    <section class="author-panel" id="tracks" hidden>
        <div class="author-panel__header">
            <h2>Мои треки</h2>
            <span>Загрузка и управление треками</span>
        </div>

        <form class="upload-form" action="/author/tracks/upload" method="post" enctype="multipart/form-data" id="upload-form" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="upload-zone" id="audio-drop-zone" tabindex="0" role="button" aria-label="Зона загрузки аудиофайла">
                <div class="upload-zone__idle">
                    <div class="upload-zone__wave" aria-hidden="true">
                        <span></span><span></span><span></span><span></span><span></span>
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                    <p class="upload-zone__title">Перетащи MP3 или WAV сюда</p>
                    <p class="upload-zone__sub">или нажми для выбора файла · до 50 МБ</p>
                </div>
                <div class="upload-zone__selected" hidden>
                    <svg class="upload-zone__file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 18V5l12-2v13"></path>
                        <circle cx="6" cy="18" r="3"></circle>
                        <circle cx="18" cy="16" r="3"></circle>
                    </svg>
                    <span class="upload-zone__file-name" id="audio-file-name">файл выбран</span>
                    <button type="button" class="upload-zone__clear" id="audio-clear" aria-label="Убрать файл">✕</button>
                </div>
                <input type="file" name="audio_file" id="audio-input" accept=".mp3,.wav,audio/mpeg,audio/wav" class="upload-zone__input" aria-hidden="true" tabindex="-1">
            </div>

            <div class="upload-meta">
                <label class="cover-picker" id="cover-picker" title="Нажми, чтобы выбрать обложку">
                    <div class="cover-picker__placeholder" id="cover-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <path d="m21 15-5-5L5 21"></path>
                        </svg>
                        <span>Обложка</span>
                        <span class="cover-picker__hint">JPG, PNG или WEBP · до 5 МБ</span>
                    </div>
                    <img class="cover-picker__preview" id="cover-preview" src="" alt="Превью обложки" hidden>
                    <input type="file" name="cover_file" id="cover-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="cover-picker__input" tabindex="-1">
                </label>

                <div class="upload-fields">
                    <label class="upload-field">
                        <span>Название трека</span>
                        <input name="title" type="text" id="track-title" placeholder="Введи название" autocomplete="off" required>
                    </label>

                    <label class="upload-field">
                        <span>Альбом <em>(необязательно)</em></span>
                        <select name="album_id">
                            <option value="">Без альбома</option>
                            <?php foreach ($albumOptions as $album): ?>
                                <option value="<?= $h($album['id']) ?>"><?= $h($album['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <button class="author-button upload-submit" type="submit" id="upload-submit" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Загрузить трек
                    </button>
                </div>
            </div>
        </form>

        <?php if ($tracks !== []): ?>
            <div class="upload-divider"></div>
            <p class="author-section-label">Все треки (<?= $h(count($tracks)) ?>)</p>
            <div class="author-tracks-list">
                <?php foreach ($tracks as $track): ?>
                    <div class="author-track-item">
                        <div class="author-track-item__cover" <?= !empty($track['cover_path']) ? 'style="background-image: url(\'' . $h((string) $track['cover_path']) . '\')"' : '' ?>></div>
                        <div class="author-track-item__info">
                            <p class="author-track-item__title"><?= $h($track['title']) ?></p>
                            <div class="author-track-item__meta">
                                <span><?= $h((string) ($track['album_title'] ?: 'Без альбома')) ?></span>
                                <span><?= $h($formatDuration($track['duration'])) ?></span>
                                <span>Воспроизведений: <?= $h($track['plays_count']) ?></span>
                                <span>Лайков: <?= $h($track['likes_count']) ?></span>
                            </div>
                        </div>
                        <div class="author-item-actions">
                            <button type="button"
                                    class="author-button author-button--ghost author-button--sm js-edit-track"
                                    data-id="<?= $h($track['id']) ?>"
                                    data-title="<?= $h($track['title']) ?>"
                                    data-album="<?= $h((string) ($track['album_id'] ?? '')) ?>">
                                Изменить
                            </button>
                            <form action="/author/tracks/delete" method="post" class="inline-form"
                                  onsubmit="return confirm('Удалить трек «<?= $h(addslashes($track['title'])) ?>»? Это действие необратимо.')">
                                <?= \App\Core\Csrf::field() ?>
                                <input type="hidden" name="track_id" value="<?= $h($track['id']) ?>">
                                <button type="submit" class="author-button author-button--danger author-button--sm">Удалить</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Modal: edit track -->
    <div class="author-modal" id="modal-edit-track" hidden aria-modal="true" role="dialog" aria-label="Редактирование трека">
        <div class="author-modal__overlay" data-modal-close></div>
        <div class="author-modal__box">
            <div class="author-modal__head">
                <h3>Редактировать трек</h3>
                <button type="button" class="author-modal__close" data-modal-close aria-label="Закрыть">✕</button>
            </div>
            <form action="/author/tracks/update" method="post" enctype="multipart/form-data" class="author-modal__form">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="track_id" id="edit-track-id">

                <label class="upload-field">
                    <span>Название трека</span>
                    <input type="text" name="title" id="edit-track-title" required autocomplete="off">
                </label>

                <label class="upload-field">
                    <span>Альбом <em>(необязательно)</em></span>
                    <select name="album_id" id="edit-track-album">
                        <option value="">Без альбома</option>
                        <?php foreach ($albumOptions as $ao): ?>
                            <option value="<?= $h($ao['id']) ?>"><?= $h($ao['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="upload-field">
                    <span>Новая обложка <em>(необязательно, заменит текущую)</em></span>
                    <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </label>

                <div class="author-modal__footer">
                    <button type="button" class="author-button author-button--ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="author-button">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Мои альбомы -->
    <section class="author-panel" id="albums" hidden>
        <div class="author-panel__header">
            <h2>Мои альбомы</h2>
            <span>Создание и управление альбомами</span>
        </div>

        <form class="upload-form" action="/author/albums/create" method="post" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <div class="upload-meta">
                <label class="cover-picker" title="Нажми, чтобы выбрать обложку альбома">
                    <div class="cover-picker__placeholder" id="album-cover-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <path d="m21 15-5-5L5 21"></path>
                        </svg>
                        <span>Обложка</span>
                        <span class="cover-picker__hint">JPG, PNG или WEBP · до 5 МБ</span>
                    </div>
                    <img class="cover-picker__preview" id="album-cover-preview" src="" alt="Превью обложки" hidden>
                    <input type="file" name="cover_file" id="album-cover-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="cover-picker__input" tabindex="-1">
                </label>

                <div class="upload-fields">
                    <label class="upload-field">
                        <span>Название альбома</span>
                        <input name="title" type="text" placeholder="Введи название" autocomplete="off" required>
                    </label>
                    <label class="upload-field">
                        <span>Дата релиза <em>(необязательно)</em></span>
                        <input name="released_at" type="date">
                    </label>
                    <button class="author-button upload-submit" type="submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Создать альбом
                    </button>
                </div>
            </div>
        </form>

        <?php if ($albums !== []): ?>
            <div class="upload-divider"></div>
            <p class="author-section-label">Все альбомы (<?= $h(count($albums)) ?>)</p>
            <div class="author-albums-list">
                <?php foreach ($albums as $album): ?>
                    <div class="author-album-item">
                        <div class="author-album-item__cover" <?= !empty($album['cover_path']) ? 'style="background-image: url(\'' . $h((string) $album['cover_path']) . '\')"' : '' ?>></div>
                        <div class="author-album-item__info">
                            <p class="author-album-item__title"><?= $h($album['title']) ?></p>
                            <div class="author-album-item__meta">
                                <span>Треков: <?= $h($album['tracks_count']) ?></span>
                                <?php if (!empty($album['released_at'])): ?>
                                    <span>Релиз: <?= $h($album['released_at']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="author-item-actions">
                            <button type="button"
                                    class="author-button author-button--ghost author-button--sm js-edit-album"
                                    data-id="<?= $h($album['id']) ?>"
                                    data-title="<?= $h($album['title']) ?>"
                                    data-released="<?= $h((string) ($album['released_at'] ?? '')) ?>">
                                Изменить
                            </button>
                            <form action="/author/albums/delete" method="post" class="inline-form"
                                  onsubmit="return confirm('Удалить альбом «<?= $h(addslashes($album['title'])) ?>»? Треки останутся, но потеряют привязку к альбому.')">
                                <?= \App\Core\Csrf::field() ?>
                                <input type="hidden" name="album_id" value="<?= $h($album['id']) ?>">
                                <button type="submit" class="author-button author-button--danger author-button--sm">Удалить</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="upload-divider"></div>
            <div class="author-stub" style="padding: 40px 0;">
                <div class="author-stub__icon">💿</div>
                <p class="author-stub__desc">У тебя пока нет альбомов. Создай первый с помощью формы выше.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Modal: edit album -->
    <div class="author-modal" id="modal-edit-album" hidden aria-modal="true" role="dialog" aria-label="Редактирование альбома">
        <div class="author-modal__overlay" data-modal-close></div>
        <div class="author-modal__box">
            <div class="author-modal__head">
                <h3>Редактировать альбом</h3>
                <button type="button" class="author-modal__close" data-modal-close aria-label="Закрыть">✕</button>
            </div>
            <form action="/author/albums/update" method="post" enctype="multipart/form-data" class="author-modal__form">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="album_id" id="edit-album-id">

                <label class="upload-field">
                    <span>Название альбома</span>
                    <input type="text" name="title" id="edit-album-title" required autocomplete="off">
                </label>

                <label class="upload-field">
                    <span>Дата релиза <em>(необязательно)</em></span>
                    <input type="date" name="released_at" id="edit-album-released">
                </label>

                <label class="upload-field">
                    <span>Новая обложка <em>(необязательно, заменит текущую)</em></span>
                    <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </label>

                <div class="author-modal__footer">
                    <button type="button" class="author-button author-button--ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="author-button">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Аналитика -->
    <section class="author-panel" id="analytics" hidden>
        <div class="author-panel__header">
            <h2>Аналитика</h2>
            <div class="analytics-period">
                <button class="author-button author-button--ghost author-button--sm analytics-period__btn analytics-period__btn--active" data-period="7">7 дней</button>
                <button class="author-button author-button--ghost author-button--sm analytics-period__btn" data-period="30">30 дней</button>
                <button class="author-button author-button--ghost author-button--sm analytics-period__btn" data-period="90">90 дней</button>
            </div>
        </div>

        <div id="analytics-loading" style="padding:40px;text-align:center;color:rgba(255,255,255,.4);">Загрузка данных...</div>

        <div id="analytics-content" hidden>
            <div class="analytics-charts">
                <div class="analytics-chart-block">
                    <h3 class="analytics-chart-title">Прослушивания</h3>
                    <canvas id="chart-listens" height="180"></canvas>
                </div>
                <div class="analytics-chart-block">
                    <h3 class="analytics-chart-title">Уникальные слушатели</h3>
                    <canvas id="chart-unique" height="180"></canvas>
                </div>
                <div class="analytics-chart-block">
                    <h3 class="analytics-chart-title">Новые подписчики</h3>
                    <canvas id="chart-subscribers" height="180"></canvas>
                </div>
            </div>

            <div class="analytics-bottom">
                <div class="analytics-stat-card">
                    <span class="analytics-stat-card__label">Досмотров до конца</span>
                    <strong class="analytics-stat-card__value" id="analytics-completion">—</strong>
                </div>
                <div class="analytics-stat-card">
                    <span class="analytics-stat-card__label">Всего уникальных слушателей</span>
                    <strong class="analytics-stat-card__value" id="analytics-unique-total">—</strong>
                </div>
            </div>

            <div class="analytics-top-tracks">
                <h3 class="analytics-chart-title">Топ треков</h3>
                <table class="analytics-table" id="analytics-top-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Название</th>
                            <th>Воспроизведений</th>
                            <th>Прослушиваний</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Профиль -->
    <section class="author-panel" id="profile" hidden>
        <div class="author-panel__header">
            <h2>Профиль</h2>
            <span>Настройки аккаунта</span>
        </div>

        <div class="profile-layout">
            <!-- Avatar upload -->
            <form class="profile-avatar-form" action="/author/profile/avatar" method="post" enctype="multipart/form-data">
                <?= \App\Core\Csrf::field() ?>
                <label class="profile-avatar-label" title="Нажми, чтобы изменить фото">
                    <div class="profile-avatar__img" id="avatar-preview"
                         <?= !empty($author['avatar']) ? 'style="background-image:url(\'' . $h((string) $author['avatar']) . '\')"' : '' ?>>
                        <?php if (empty($author['avatar'])): ?>
                            <?= $h(mb_strtoupper(mb_substr((string) ($author['name'] ?? '?'), 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <span class="profile-avatar__hint">Нажми для изменения фото</span>
                    <input type="file" name="avatar_file" id="avatar-input"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           class="profile-avatar__input" tabindex="-1">
                </label>
                <button type="submit" class="author-button author-button--sm" id="avatar-submit" disabled>
                    Сохранить фото
                </button>
            </form>

            <!-- Name / bio / password -->
            <form class="profile-info-form upload-form" action="/author/profile/update" method="post">
                <?= \App\Core\Csrf::field() ?>

                <label class="upload-field">
                    <span>Имя / псевдоним</span>
                    <input type="text" name="name" value="<?= $h($author['name'] ?? '') ?>"
                           maxlength="255" required autocomplete="off">
                </label>

                <label class="upload-field">
                    <span>Биография <em>(необязательно)</em></span>
                    <textarea name="bio" rows="4" maxlength="1000"
                              placeholder="Расскажи о себе..."><?= $h($author['bio'] ?? '') ?></textarea>
                </label>

                <div class="upload-divider"></div>
                <p class="author-section-label">Смена пароля <em style="font-weight:400;opacity:.6">(заполни только если хочешь изменить)</em></p>

                <label class="upload-field">
                    <span>Текущий пароль</span>
                    <input type="password" name="current_password" autocomplete="current-password">
                </label>

                <label class="upload-field">
                    <span>Новый пароль <em>(минимум 6 символов)</em></span>
                    <input type="password" name="new_password" autocomplete="new-password" minlength="6">
                </label>

                <label class="upload-field">
                    <span>Повтори новый пароль</span>
                    <input type="password" name="confirm_password" autocomplete="new-password">
                </label>

                <button type="submit" class="author-button">Сохранить изменения</button>
            </form>
        </div>
    </section>

</div>

<!-- ── Глобальный плеер ─────────────────────────────── -->
<footer class="player-bar player-bar--fixed" data-player-root aria-label="Музыкальный плеер">
    <div class="player__now">
        <div class="player__art" data-player-cover></div>
        <div class="player__info">
            <p class="player__title" data-player-title>Трек не выбран</p>
            <p class="player__artist" data-player-artist>Выбери трек на главной</p>
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

<audio preload="none" data-player-audio></audio>
<script>
window.PLAYER_CONFIG = { csrfToken: '<?= \App\Core\Csrf::token() ?>', playlist: [], fetchUrl: '/player/playlist' };
</script>
<script src="/assets/js/player.js"></script>

<script>
(function () {
    /* ---------- tab switching ---------- */
    var panels = document.querySelectorAll('.author-panel');
    var tabs   = document.querySelectorAll('.author-tabs__item');
    var ids    = Array.from(panels).map(function (p) { return p.id; });

    function activateTab(hash) {
        var id = (hash || '').replace('#', '') || 'overview';
        if (ids.indexOf(id) === -1) { id = 'overview'; }
        panels.forEach(function (p) { p.hidden = p.id !== id; });
        tabs.forEach(function (a) {
            a.classList.toggle('author-tabs__item--active', a.getAttribute('href') === '#' + id);
        });
    }

    window.addEventListener('hashchange', function () {
        try { activateTab(location.hash); } catch (e) { activateTab(''); }
    });
    try { activateTab(location.hash); } catch (e) { activateTab(''); }

    /* ---------- upload form ---------- */
    var dropZone   = document.getElementById('audio-drop-zone');
    var audioInput = document.getElementById('audio-input');
    var idleEl     = dropZone && dropZone.querySelector('.upload-zone__idle');
    var selectedEl = dropZone && dropZone.querySelector('.upload-zone__selected');
    var fileNameEl = document.getElementById('audio-file-name');
    var clearBtn   = document.getElementById('audio-clear');
    var titleInput = document.getElementById('track-title');
    var submitBtn  = document.getElementById('upload-submit');
    var coverInput = document.getElementById('cover-input');
    var coverPreview = document.getElementById('cover-preview');
    var coverPlaceholder = document.getElementById('cover-placeholder');

    var audioReady = false;

    function updateSubmit() {
        if (!submitBtn) { return; }
        var hasTitle = titleInput && titleInput.value.trim() !== '';
        submitBtn.disabled = !(audioReady && hasTitle);
    }

    function showAudioFile(name) {
        audioReady = true;
        if (fileNameEl) { fileNameEl.textContent = name; }
        if (idleEl)     { idleEl.hidden = true; }
        if (selectedEl) { selectedEl.hidden = false; }
        dropZone && dropZone.classList.add('upload-zone--has-file');
        updateSubmit();
    }

    function clearAudio() {
        audioReady = false;
        if (audioInput) { audioInput.value = ''; }
        if (idleEl)     { idleEl.hidden = false; }
        if (selectedEl) { selectedEl.hidden = true; }
        dropZone && dropZone.classList.remove('upload-zone--has-file');
        updateSubmit();
    }

    function isAudioFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        return ext === 'mp3' || ext === 'wav';
    }

    if (dropZone) {
        dropZone.addEventListener('click', function (e) {
            if (e.target === clearBtn || (clearBtn && clearBtn.contains(e.target))) { return; }
            if (audioInput) { audioInput.click(); }
        });

        dropZone.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); audioInput && audioInput.click(); }
        });

        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropZone.classList.add('upload-zone--drag');
        });

        dropZone.addEventListener('dragleave', function (e) {
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('upload-zone--drag');
            }
        });

        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropZone.classList.remove('upload-zone--drag');

            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || files.length === 0) { return; }

            var file = files[0];
            if (!isAudioFile(file)) {
                dropZone.classList.add('upload-zone--error');
                setTimeout(function () { dropZone.classList.remove('upload-zone--error'); }, 1400);
                return;
            }

            var dt = new DataTransfer();
            dt.items.add(file);
            audioInput.files = dt.files;
            showAudioFile(file.name);
        });
    }

    if (audioInput) {
        audioInput.addEventListener('change', function () {
            if (audioInput.files && audioInput.files[0]) {
                showAudioFile(audioInput.files[0].name);
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            clearAudio();
        });
    }

    if (titleInput) {
        titleInput.addEventListener('input', updateSubmit);
    }

    /* cover preview */
    if (coverInput) {
        coverInput.addEventListener('change', function () {
            var file = coverInput.files && coverInput.files[0];
            if (!file) { return; }

            var reader = new FileReader();
            reader.onload = function (e) {
                if (coverPreview)     { coverPreview.src = e.target.result; coverPreview.hidden = false; }
                if (coverPlaceholder) { coverPlaceholder.hidden = true; }
            };
            reader.readAsDataURL(file);
        });
    }

    /* loading state on submit */
    var form = document.getElementById('upload-form');
    if (form) {
        form.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Загружается…';
            }
        });
    }

    /* album cover preview */
    var albumCoverInput       = document.getElementById('album-cover-input');
    var albumCoverPreview     = document.getElementById('album-cover-preview');
    var albumCoverPlaceholder = document.getElementById('album-cover-placeholder');

    if (albumCoverInput) {
        albumCoverInput.addEventListener('change', function () {
            var file = albumCoverInput.files && albumCoverInput.files[0];
            if (!file) { return; }
            var reader = new FileReader();
            reader.onload = function (e) {
                if (albumCoverPreview)     { albumCoverPreview.src = e.target.result; albumCoverPreview.hidden = false; }
                if (albumCoverPlaceholder) { albumCoverPlaceholder.hidden = true; }
            };
            reader.readAsDataURL(file);
        });
    }

    /* ---------- modals ---------- */
    function openModal(id) {
        var m = document.getElementById(id);
        if (m) { m.hidden = false; document.body.style.overflow = 'hidden'; }
    }

    function closeAllModals() {
        document.querySelectorAll('.author-modal').forEach(function (m) { m.hidden = true; });
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-modal-close]')) { closeAllModals(); }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeAllModals(); }
    });

    /* open edit-track modal */
    document.querySelectorAll('.js-edit-track').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id    = btn.dataset.id;
            var title = btn.dataset.title;
            var album = btn.dataset.album;

            var idInput    = document.getElementById('edit-track-id');
            var titleInput = document.getElementById('edit-track-title');
            var albumSel   = document.getElementById('edit-track-album');

            if (idInput)    { idInput.value    = id; }
            if (titleInput) { titleInput.value = title; }
            if (albumSel)   { albumSel.value   = album || ''; }

            openModal('modal-edit-track');
        });
    });

    /* open edit-album modal */
    document.querySelectorAll('.js-edit-album').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id       = btn.dataset.id;
            var title    = btn.dataset.title;
            var released = btn.dataset.released;

            var idInput       = document.getElementById('edit-album-id');
            var titleInput    = document.getElementById('edit-album-title');
            var releasedInput = document.getElementById('edit-album-released');

            if (idInput)       { idInput.value       = id; }
            if (titleInput)    { titleInput.value     = title; }
            if (releasedInput) { releasedInput.value  = released || ''; }

            openModal('modal-edit-album');
        });
    });
}());
</script>

<script>
/* ── Analytics ─────────────────────────────────────────── */
(function () {
    'use strict';

    var analyticsLoaded = false;
    var chartListens    = null;
    var chartUnique     = null;
    var chartSubs       = null;
    var currentPeriod   = 7;

    var chartOptions = {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#9896a8', maxTicksLimit: 10 } },
            y: { grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#9896a8', stepSize: 1 }, beginAtZero: true }
        }
    };

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function buildChartData(series) {
        return {
            labels: series.map(function (r) { return r.label; }),
            datasets: [{
                data:            series.map(function (r) { return r.count; }),
                borderColor:     '#8b5cf6',
                backgroundColor: 'rgba(139,92,246,0.12)',
                borderWidth:     2,
                pointRadius:     3,
                fill:            true,
                tension:         0.35
            }]
        };
    }

    function buildChartDataColored(series, color) {
        return {
            labels: series.map(function (r) { return r.label; }),
            datasets: [{
                data:            series.map(function (r) { return r.count; }),
                borderColor:     color,
                backgroundColor: color.replace(')', ', 0.12)').replace('rgb', 'rgba'),
                borderWidth:     2,
                pointRadius:     3,
                fill:            true,
                tension:         0.35
            }]
        };
    }

    function renderAnalytics(data) {
        var ctxL = document.getElementById('chart-listens');
        var ctxU = document.getElementById('chart-unique');
        var ctxS = document.getElementById('chart-subscribers');

        if (ctxL) {
            if (chartListens) { chartListens.destroy(); }
            chartListens = new Chart(ctxL.getContext('2d'), { type: 'line', data: buildChartData(data.listens || []), options: chartOptions });
        }

        if (ctxU) {
            if (chartUnique) { chartUnique.destroy(); }
            chartUnique = new Chart(ctxU.getContext('2d'), { type: 'line', data: buildChartDataColored(data.uniqueListeners || [], '#22d3ee'), options: chartOptions });
        }

        if (ctxS) {
            if (chartSubs) { chartSubs.destroy(); }
            chartSubs = new Chart(ctxS.getContext('2d'), { type: 'line', data: buildChartData(data.subscribers || []), options: chartOptions });
        }

        var completionEl = document.getElementById('analytics-completion');
        if (completionEl) {
            completionEl.textContent = Math.round((data.completionRate || 0) * 100) + '%';
        }

        var uniqueTotalEl = document.getElementById('analytics-unique-total');
        if (uniqueTotalEl) {
            uniqueTotalEl.textContent = data.totalUniqueListeners !== undefined ? data.totalUniqueListeners : '—';
        }

        var tbody = document.querySelector('#analytics-top-table tbody');
        if (tbody) {
            tbody.innerHTML = '';
            (data.topTracks || []).forEach(function (t, i) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + escHtml(t.title || '') + '</td>' +
                    '<td>' + (t.plays_count || 0) + '</td>' +
                    '<td>' + (t.listens_count || 0) + '</td>';
                tbody.appendChild(tr);
            });

            if ((data.topTracks || []).length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:rgba(255,255,255,.3);padding:20px">Данных пока нет</td></tr>';
            }
        }
    }

    function loadAnalytics(period) {
        currentPeriod = period;
        var loadingEl = document.getElementById('analytics-loading');
        var contentEl = document.getElementById('analytics-content');
        if (loadingEl) { loadingEl.hidden = false; loadingEl.textContent = 'Загрузка данных...'; }
        if (contentEl) { contentEl.hidden = true; }

        fetch('/author/analytics/data?period=' + period)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderAnalytics(data);
                if (loadingEl) { loadingEl.hidden = true; }
                if (contentEl) { contentEl.hidden = false; }
            })
            .catch(function () {
                if (loadingEl) { loadingEl.textContent = 'Не удалось загрузить данные.'; }
            });
    }

    document.querySelectorAll('.author-tabs__item').forEach(function (tab) {
        tab.addEventListener('click', function () {
            if (tab.getAttribute('href') === '#analytics' && !analyticsLoaded) {
                analyticsLoaded = true;
                loadAnalytics(currentPeriod);
            }
        });
    });

    document.querySelectorAll('.analytics-period__btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.analytics-period__btn').forEach(function (b) {
                b.classList.remove('analytics-period__btn--active');
            });
            btn.classList.add('analytics-period__btn--active');
            analyticsLoaded = true;
            loadAnalytics(parseInt(btn.dataset.period, 10));
        });
    });
}());

/* ── Avatar preview ────────────────────────────────────── */
(function () {
    'use strict';

    var avatarInput   = document.getElementById('avatar-input');
    var avatarPreview = document.getElementById('avatar-preview');
    var avatarSubmit  = document.getElementById('avatar-submit');

    if (!avatarInput) { return; }

    avatarInput.addEventListener('change', function () {
        var file = avatarInput.files && avatarInput.files[0];
        if (!file) { return; }
        var reader = new FileReader();
        reader.onload = function (e) {
            if (avatarPreview) {
                avatarPreview.style.backgroundImage = 'url(\'' + e.target.result + '\')';
                avatarPreview.textContent = '';
            }
            if (avatarSubmit) { avatarSubmit.disabled = false; }
        };
        reader.readAsDataURL(file);
    });
}());
</script>
</body>
</html>
