<?php

declare(strict_types=1);

$summary = is_array($summary ?? null) ? $summary : [];
$users = is_array($users ?? null) ? $users : [];
$albums = is_array($albums ?? null) ? $albums : [];
$tracks = is_array($tracks ?? null) ? $tracks : [];
$authors = is_array($authors ?? null) ? $authors : [];
$albumOptions = is_array($albumOptions ?? null) ? $albumOptions : [];
$listenChart = is_array($listenChart ?? null) ? $listenChart : [];
$registrationChart = is_array($registrationChart ?? null) ? $registrationChart : [];
$topTracks  = is_array($topTracks ?? null) ? $topTracks : [];
$topAuthors = is_array($topAuthors ?? null) ? $topAuthors : [];
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$roleLabels = [
    'listener' => 'Слушатель',
    'author' => 'Автор',
    'admin' => 'Администратор',
];
$summaryCards = [
    'Пользователи' => $summary['users_total'] ?? 0,
    'Слушатели' => $summary['listeners_total'] ?? 0,
    'Авторы' => $summary['authors_total'] ?? 0,
    'Админы' => $summary['admins_total'] ?? 0,
    'Альбомы' => $summary['albums_total'] ?? 0,
    'Треки' => $summary['tracks_total'] ?? 0,
    'Плейлисты' => $summary['playlists_total'] ?? 0,
    'Прослушивания' => $summary['listens_total'] ?? 0,
];
$maxTopTrack   = max(array_map(static fn(array $item): int => (int) ($item['listens_count'] ?? 0), $topTracks) ?: [1]);
$maxTopAuthor  = max(array_map(static fn(array $item): int => (int) ($item['listens_count'] ?? 0), $topAuthors) ?: [1]);
$csrf = \App\Core\Csrf::field();
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Richsound</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
</head>
<body class="admin-page">
<div class="admin-shell">

    <header class="admin-header">
        <div>
            <p class="admin-header__eyebrow">Панель администратора</p>
            <h1>Управление платформой Richsound</h1>
            <p class="admin-header__meta"><?= $h($admin['name'] ?? 'Администратор') ?> · <?= $h($admin['email'] ?? '') ?></p>
        </div>
        <div class="admin-header__actions">
            <a class="admin-button admin-button--ghost" href="/">На главную</a>
            <form action="/logout" method="post">
                <?= $csrf ?>
                <button class="admin-button" type="submit">Выйти</button>
            </form>
        </div>
    </header>

    <?php if (!empty($success)): ?>
        <div class="admin-alert admin-alert--success"><?= $h($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="admin-alert admin-alert--error"><?= $h($error) ?></div>
    <?php endif; ?>

    <section class="admin-summary">
        <?php foreach ($summaryCards as $label => $value): ?>
            <article class="summary-card">
                <span class="summary-card__label"><?= $h($label) ?></span>
                <strong class="summary-card__value"><?= $h($value) ?></strong>
            </article>
        <?php endforeach; ?>
    </section>

    <nav class="admin-tabs">
        <button class="admin-tab-btn" data-tab="overview">Обзор</button>
        <button class="admin-tab-btn" data-tab="users">Пользователи</button>
        <button class="admin-tab-btn" data-tab="albums">Альбомы</button>
        <button class="admin-tab-btn" data-tab="tracks">Треки</button>
    </nav>

    <!-- ═══ Overview Tab ═════════════════════════════════════════════ -->
    <div class="admin-tab-panel" data-panel="overview">

        <section class="panel" style="margin-bottom:20px">
            <div class="panel__header">
                <h2>Аналитика</h2>
                <div class="period-toggle">
                    <button class="period-btn active" data-days="7">7 дней</button>
                    <button class="period-btn" data-days="30">30 дней</button>
                </div>
            </div>
            <div class="admin-grid admin-grid--charts">
                <div>
                    <p class="chart-label">Прослушивания</p>
                    <div class="chart-wrapper" style="position:relative;height:220px">
                        <canvas id="chart-listens"></canvas>
                    </div>
                </div>
                <div>
                    <p class="chart-label">Регистрации</p>
                    <div class="chart-wrapper" style="position:relative;height:220px">
                        <canvas id="chart-registrations"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="admin-grid admin-grid--wide" style="grid-template-columns:1fr 1fr;gap:20px">
            <article class="panel">
                <div class="panel__header">
                    <h2>Топ треков</h2>
                    <span id="top-tracks-period-label">За 7 дней</span>
                </div>
                <div class="ranking-list" id="top-tracks-list">
                    <?php foreach ($topTracks as $track): ?>
                        <?php $width = max(6, (int) round(((int) $track['listens_count'] / max(1, $maxTopTrack)) * 100)); ?>
                        <div class="ranking-item">
                            <div class="ranking-item__head">
                                <strong><?= $h($track['title']) ?></strong>
                                <span><?= $h($track['author_name']) ?></span>
                            </div>
                            <div class="ranking-item__meter">
                                <div class="ranking-item__fill" style="width:<?= $width ?>%"></div>
                            </div>
                            <div class="ranking-item__meta">
                                <span>Прослушивания: <?= $h($track['listens_count']) ?></span>
                                <span>Лайки: <?= $h($track['likes_count']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($topTracks === []): ?>
                        <p style="color:rgba(255,255,255,.3);padding:20px 0;text-align:center">Данных пока нет</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel">
                <div class="panel__header">
                    <h2>Топ авторов</h2>
                    <span>По числу прослушиваний (все время)</span>
                </div>
                <div class="ranking-list">
                    <?php foreach ($topAuthors as $author): ?>
                        <?php $width = max(6, (int) round(((int) $author['listens_count'] / max(1, $maxTopAuthor)) * 100)); ?>
                        <div class="ranking-item">
                            <div class="ranking-item__head">
                                <strong><?= $h($author['name']) ?></strong>
                                <span><?= $h($author['unique_listeners']) ?> уник. слушателей</span>
                            </div>
                            <div class="ranking-item__meter">
                                <div class="ranking-item__fill" style="width:<?= $width ?>%;background:linear-gradient(90deg,#22d3ee,#8b5cf6)"></div>
                            </div>
                            <div class="ranking-item__meta">
                                <span>Прослушивания: <?= $h($author['listens_count']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($topAuthors === []): ?>
                        <p style="color:rgba(255,255,255,.3);padding:20px 0;text-align:center">Данных пока нет</p>
                    <?php endif; ?>
                </div>
            </article>
        </section>

    </div>

    <!-- ═══ Users Tab ════════════════════════════════════════════════ -->
    <div class="admin-tab-panel hidden" data-panel="users">

        <div class="create-form-section">
            <button class="create-form-toggle" onclick="adminToggleCreate('create-users')">
                + Создать пользователя
                <span class="toggle-arrow">▼</span>
            </button>
            <div class="create-form-body" id="create-users">
                <form class="entity-form" action="/admin/users/create" method="post">
                    <?= $csrf ?>
                    <label><span>Имя</span><input name="name" type="text" required></label>
                    <label><span>Email</span><input name="email" type="email" required></label>
                    <label>
                        <span>Роль</span>
                        <select name="role">
                            <option value="listener">Слушатель</option>
                            <option value="author">Автор</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </label>
                    <label><span>Пароль</span><input name="password" type="password" minlength="6" required></label>
                    <button class="admin-button" type="submit">Создать пользователя</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>Пользователи</h2>
                <span><?= count($users) ?> записей</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Треков</th>
                            <th>Подписчиков</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="user-avatar"><?= $h(mb_strtoupper(mb_substr((string) ($user['name'] ?? '?'), 0, 1))) ?></div>
                                    <div>
                                        <div style="font-weight:600"><?= $h($user['name']) ?></div>
                                        <div style="font-size:0.78rem;color:var(--admin-muted)">ID: <?= $h($user['id']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= $h($user['email']) ?></td>
                            <td><span class="role-badge role-<?= $h($user['role']) ?>"><?= $h($roleLabels[$user['role']] ?? $user['role']) ?></span></td>
                            <td><?= $h($user['tracks_count']) ?></td>
                            <td><?= $h($user['subscribers_count']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="admin-button admin-button--ghost admin-button--sm" onclick="adminOpenModal('modal-user-<?= $h($user['id']) ?>')">Изменить</button>
                                    <form action="/admin/users/delete" method="post" style="display:inline">
                                        <?= $csrf ?>
                                        <input type="hidden" name="id" value="<?= $h($user['id']) ?>">
                                        <button class="admin-button admin-button--danger admin-button--sm" onclick="return confirm('Удалить пользователя? Это действие необратимо.')">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Users modals -->
    <?php foreach ($users as $user): ?>
    <div class="admin-modal" id="modal-user-<?= $h($user['id']) ?>">
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Редактировать пользователя</h3>
                <button class="admin-modal__close" onclick="adminCloseModal('modal-user-<?= $h($user['id']) ?>')">✕</button>
            </div>
            <form class="entity-form" action="/admin/users/update" method="post">
                <?= $csrf ?>
                <input type="hidden" name="id" value="<?= $h($user['id']) ?>">
                <label><span>Имя</span><input name="name" type="text" value="<?= $h($user['name']) ?>" required></label>
                <label><span>Email</span><input name="email" type="email" value="<?= $h($user['email']) ?>" required></label>
                <label>
                    <span>Роль</span>
                    <select name="role">
                        <?php foreach ($roleLabels as $role => $label): ?>
                            <option value="<?= $h($role) ?>" <?= ($user['role'] ?? '') === $role ? 'selected' : '' ?>><?= $h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Новый пароль</span><input name="password" type="password" minlength="6" placeholder="Оставь пустым, чтобы не менять"></label>
                <p style="margin:8px 0 0;font-size:0.82rem;color:var(--admin-muted)">
                    Альбомов: <?= $h($user['albums_count']) ?> · Треков: <?= $h($user['tracks_count']) ?> · Подписчиков: <?= $h($user['subscribers_count']) ?>
                </p>
                <div class="admin-modal__footer">
                    <button type="button" class="admin-button admin-button--ghost" onclick="adminCloseModal('modal-user-<?= $h($user['id']) ?>')">Отмена</button>
                    <button class="admin-button" type="submit">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ═══ Albums Tab ════════════════════════════════════════════════ -->
    <div class="admin-tab-panel hidden" data-panel="albums">

        <div class="create-form-section">
            <button class="create-form-toggle" onclick="adminToggleCreate('create-albums')">
                + Создать альбом
                <span class="toggle-arrow">▼</span>
            </button>
            <div class="create-form-body" id="create-albums">
                <form class="entity-form" action="/admin/albums/create" method="post">
                    <?= $csrf ?>
                    <label>
                        <span>Автор</span>
                        <select name="author_id" required>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= $h($author['id']) ?>"><?= $h($author['name']) ?> · <?= $h($roleLabels[$author['role']] ?? $author['role']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span>Название</span><input name="title" type="text" required></label>
                    <label>
                        <span>Обложка (URL)</span>
                        <input name="cover_path" type="text" placeholder="/uploads/covers/album.jpg"
                               oninput="adminPreviewCover('create-album-cover', this.value)">
                    </label>
                    <div class="cover-preview-wrap">
                        <img class="cover-preview" id="create-album-cover" src="" alt="Обложка">
                        <span style="font-size:0.8rem;color:var(--admin-muted)">Предпросмотр</span>
                    </div>
                    <label><span>Дата релиза</span><input name="released_at" type="date"></label>
                    <button class="admin-button" type="submit">Создать альбом</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>Альбомы</h2>
                <span><?= count($albums) ?> записей</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Обложка</th>
                            <th>Название</th>
                            <th>Автор</th>
                            <th>Треков</th>
                            <th>Дата релиза</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($albums as $album): ?>
                        <tr>
                            <td>
                                <?php if (!empty($album['cover_path'])): ?>
                                    <img class="cover-thumb" src="<?= $h($album['cover_path']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <div class="cover-thumb-placeholder">♪</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600"><?= $h($album['title']) ?></div>
                                <div style="font-size:0.78rem;color:var(--admin-muted)">ID: <?= $h($album['id']) ?></div>
                            </td>
                            <td><?= $h($album['author_name']) ?></td>
                            <td><?= $h($album['tracks_count']) ?></td>
                            <td><?= $h($album['released_at'] ?: '—') ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="admin-button admin-button--ghost admin-button--sm" onclick="adminOpenModal('modal-album-<?= $h($album['id']) ?>')">Изменить</button>
                                    <form action="/admin/albums/delete" method="post" style="display:inline">
                                        <?= $csrf ?>
                                        <input type="hidden" name="id" value="<?= $h($album['id']) ?>">
                                        <button class="admin-button admin-button--danger admin-button--sm" onclick="return confirm('Удалить альбом? Это действие необратимо.')">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Albums modals -->
    <?php foreach ($albums as $album): ?>
    <div class="admin-modal" id="modal-album-<?= $h($album['id']) ?>">
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Редактировать альбом</h3>
                <button class="admin-modal__close" onclick="adminCloseModal('modal-album-<?= $h($album['id']) ?>')">✕</button>
            </div>
            <form class="entity-form" action="/admin/albums/update" method="post">
                <?= $csrf ?>
                <input type="hidden" name="id" value="<?= $h($album['id']) ?>">
                <label>
                    <span>Автор</span>
                    <select name="author_id">
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= $h($author['id']) ?>" <?= (int) $album['author_id'] === (int) $author['id'] ? 'selected' : '' ?>><?= $h($author['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Название</span><input name="title" type="text" value="<?= $h($album['title']) ?>" required></label>
                <label>
                    <span>Обложка (URL)</span>
                    <input name="cover_path" type="text" value="<?= $h($album['cover_path']) ?>"
                           oninput="adminPreviewCover('album-cover-<?= $h($album['id']) ?>', this.value)">
                </label>
                <div class="cover-preview-wrap">
                    <img class="cover-preview<?= !empty($album['cover_path']) ? ' visible' : '' ?>"
                         id="album-cover-<?= $h($album['id']) ?>"
                         src="<?= $h($album['cover_path'] ?? '') ?>" alt="Обложка">
                    <span style="font-size:0.8rem;color:var(--admin-muted)">Предпросмотр</span>
                </div>
                <label><span>Дата релиза</span><input name="released_at" type="date" value="<?= $h($album['released_at']) ?>"></label>
                <div class="admin-modal__footer">
                    <button type="button" class="admin-button admin-button--ghost" onclick="adminCloseModal('modal-album-<?= $h($album['id']) ?>')">Отмена</button>
                    <button class="admin-button" type="submit">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ═══ Tracks Tab ════════════════════════════════════════════════ -->
    <div class="admin-tab-panel hidden" data-panel="tracks">

        <div class="create-form-section">
            <button class="create-form-toggle" onclick="adminToggleCreate('create-tracks')">
                + Создать трек
                <span class="toggle-arrow">▼</span>
            </button>
            <div class="create-form-body" id="create-tracks">
                <form class="entity-form" action="/admin/tracks/create" method="post">
                    <?= $csrf ?>
                    <label>
                        <span>Автор</span>
                        <select name="author_id" required>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= $h($author['id']) ?>"><?= $h($author['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Альбом</span>
                        <select name="album_id">
                            <option value="">Без альбома</option>
                            <?php foreach ($albumOptions as $album): ?>
                                <option value="<?= $h($album['id']) ?>"><?= $h($album['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span>Название</span><input name="title" type="text" required></label>
                    <label><span>Путь к файлу</span><input name="file_path" type="text" placeholder="/uploads/tracks/song.mp3" required></label>
                    <label>
                        <span>Обложка (URL)</span>
                        <input name="cover_path" type="text" placeholder="/uploads/covers/track.jpg"
                               oninput="adminPreviewCover('create-track-cover', this.value)">
                    </label>
                    <div class="cover-preview-wrap">
                        <img class="cover-preview" id="create-track-cover" src="" alt="Обложка">
                        <span style="font-size:0.8rem;color:var(--admin-muted)">Предпросмотр</span>
                    </div>
                    <label><span>Длительность, сек</span><input name="duration" type="number" min="0"></label>
                    <label><span>Счётчик воспроизведений</span><input name="plays_count" type="number" min="0" value="0"></label>
                    <button class="admin-button" type="submit">Создать трек</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>Треки</h2>
                <span><?= count($tracks) ?> записей</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Обложка</th>
                            <th>Название</th>
                            <th>Автор</th>
                            <th>Альбом</th>
                            <th>Длит.</th>
                            <th>Прослуш.</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tracks as $track): ?>
                        <?php
                        $dur = (int) ($track['duration'] ?? 0);
                        $durStr = $dur > 0 ? sprintf('%d:%02d', intdiv($dur, 60), $dur % 60) : '—';
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($track['cover_path'])): ?>
                                    <img class="cover-thumb" src="<?= $h($track['cover_path']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <div class="cover-thumb-placeholder">♪</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600"><?= $h($track['title']) ?></div>
                                <div style="font-size:0.78rem;color:var(--admin-muted)">ID: <?= $h($track['id']) ?></div>
                            </td>
                            <td><?= $h($track['author_name']) ?></td>
                            <td><?= $h($track['album_title'] ?: '—') ?></td>
                            <td><?= $h($durStr) ?></td>
                            <td><?= $h($track['listens_count']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="admin-button admin-button--ghost admin-button--sm" onclick="adminOpenModal('modal-track-<?= $h($track['id']) ?>')">Изменить</button>
                                    <form action="/admin/tracks/delete" method="post" style="display:inline">
                                        <?= $csrf ?>
                                        <input type="hidden" name="id" value="<?= $h($track['id']) ?>">
                                        <button class="admin-button admin-button--danger admin-button--sm" onclick="return confirm('Удалить трек? Это действие необратимо.')">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Tracks modals -->
    <?php foreach ($tracks as $track): ?>
    <div class="admin-modal" id="modal-track-<?= $h($track['id']) ?>">
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Редактировать трек</h3>
                <button class="admin-modal__close" onclick="adminCloseModal('modal-track-<?= $h($track['id']) ?>')">✕</button>
            </div>
            <form class="entity-form" action="/admin/tracks/update" method="post">
                <?= $csrf ?>
                <input type="hidden" name="id" value="<?= $h($track['id']) ?>">
                <label>
                    <span>Автор</span>
                    <select name="author_id">
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= $h($author['id']) ?>" <?= (int) $track['author_id'] === (int) $author['id'] ? 'selected' : '' ?>><?= $h($author['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Альбом</span>
                    <select name="album_id">
                        <option value="">Без альбома</option>
                        <?php foreach ($albumOptions as $album): ?>
                            <option value="<?= $h($album['id']) ?>" <?= (int) ($track['album_id'] ?? 0) === (int) $album['id'] ? 'selected' : '' ?>><?= $h($album['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Название</span><input name="title" type="text" value="<?= $h($track['title']) ?>" required></label>
                <label><span>Путь к файлу</span><input name="file_path" type="text" value="<?= $h($track['file_path']) ?>" required></label>
                <label>
                    <span>Обложка (URL)</span>
                    <input name="cover_path" type="text" value="<?= $h($track['cover_path']) ?>"
                           oninput="adminPreviewCover('track-cover-<?= $h($track['id']) ?>', this.value)">
                </label>
                <div class="cover-preview-wrap">
                    <img class="cover-preview<?= !empty($track['cover_path']) ? ' visible' : '' ?>"
                         id="track-cover-<?= $h($track['id']) ?>"
                         src="<?= $h($track['cover_path'] ?? '') ?>" alt="Обложка">
                    <span style="font-size:0.8rem;color:var(--admin-muted)">Предпросмотр</span>
                </div>
                <label><span>Длительность, сек</span><input name="duration" type="number" min="0" value="<?= $h($track['duration']) ?>"></label>
                <label><span>Воспроизведения</span><input name="plays_count" type="number" min="0" value="<?= $h($track['plays_count']) ?>"></label>
                <p style="margin:8px 0 0;font-size:0.82rem;color:var(--admin-muted)">
                    Лайки: <?= $h($track['likes_count']) ?> · Прослушивания: <?= $h($track['listens_count']) ?>
                </p>
                <div class="admin-modal__footer">
                    <button type="button" class="admin-button admin-button--ghost" onclick="adminCloseModal('modal-track-<?= $h($track['id']) ?>')">Отмена</button>
                    <button class="admin-button" type="submit">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

</div><!-- /admin-shell -->

<script>
(function () {
    // ── Chart.js ──────────────────────────────────────────────────
    Chart.defaults.color = '#b3b3b3';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
    Chart.defaults.font.family = '"Sora", system-ui, sans-serif';
    Chart.defaults.font.size = 12;

    var listenLabels = <?= json_encode(array_column($listenChart, 'label'), JSON_UNESCAPED_UNICODE) ?>;
    var listenValues = <?= json_encode(array_map(static fn($r) => (int) $r['total'], $listenChart)) ?>;
    var regLabels    = <?= json_encode(array_column($registrationChart, 'label'), JSON_UNESCAPED_UNICODE) ?>;
    var regValues    = <?= json_encode(array_map(static fn($r) => (int) $r['total'], $registrationChart)) ?>;

    var sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(14,14,22,0.95)',
                borderColor: 'rgba(255,255,255,0.1)',
                borderWidth: 1,
                padding: 10,
                titleColor: '#fff',
                bodyColor: '#b3b3b3',
            }
        },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#b3b3b3' } },
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#b3b3b3', precision: 0 } }
        }
    };

    var chartListens = new Chart(document.getElementById('chart-listens'), {
        type: 'line',
        data: {
            labels: listenLabels,
            datasets: [{
                data: listenValues,
                fill: true,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139,92,246,0.15)',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: sharedOptions
    });

    var chartRegs = new Chart(document.getElementById('chart-registrations'), {
        type: 'line',
        data: {
            labels: regLabels,
            datasets: [{
                data: regValues,
                fill: true,
                borderColor: '#22d3ee',
                backgroundColor: 'rgba(34,211,238,0.12)',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: sharedOptions
    });

    // ── Period toggle ─────────────────────────────────────────────
    function escHtmlAdmin(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    document.querySelectorAll('.period-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.period-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var days = btn.dataset.days;
            fetch('/admin/analytics?period=' + days)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    chartListens.data.labels = data.listens.map(function (r) { return r.label; });
                    chartListens.data.datasets[0].data = data.listens.map(function (r) { return r.total; });
                    chartListens.update();
                    chartRegs.data.labels = data.registrations.map(function (r) { return r.label; });
                    chartRegs.data.datasets[0].data = data.registrations.map(function (r) { return r.total; });
                    chartRegs.update();

                    /* update top tracks */
                    var topList = document.getElementById('top-tracks-list');
                    var periodLabel = document.getElementById('top-tracks-period-label');
                    if (periodLabel) { periodLabel.textContent = 'За ' + days + ' дней'; }
                    if (topList && data.topTracks) {
                        var maxL = Math.max.apply(null, data.topTracks.map(function (t) { return t.listens_count || 0; }).concat([1]));
                        topList.innerHTML = data.topTracks.length === 0
                            ? '<p style="color:rgba(255,255,255,.3);padding:20px 0;text-align:center">Данных пока нет</p>'
                            : data.topTracks.map(function (t) {
                                var w = Math.max(6, Math.round(((t.listens_count || 0) / maxL) * 100));
                                return '<div class="ranking-item">' +
                                    '<div class="ranking-item__head"><strong>' + escHtmlAdmin(t.title || '') + '</strong>' +
                                    '<span>' + escHtmlAdmin(t.author_name || '') + '</span></div>' +
                                    '<div class="ranking-item__meter"><div class="ranking-item__fill" style="width:' + w + '%"></div></div>' +
                                    '<div class="ranking-item__meta">' +
                                    '<span>Прослушивания: ' + (t.listens_count || 0) + '</span>' +
                                    '<span>Лайки: ' + (t.likes_count || 0) + '</span></div></div>';
                            }).join('');
                    }
                });
        });
    });

    // ── Tab routing ───────────────────────────────────────────────
    var tabBtns   = document.querySelectorAll('.admin-tab-btn');
    var tabPanels = document.querySelectorAll('.admin-tab-panel');
    var validTabs = ['overview', 'users', 'albums', 'tracks'];

    function showTab(id) {
        if (!validTabs.includes(id)) id = 'overview';
        tabBtns.forEach(function (b) { b.classList.toggle('active', b.dataset.tab === id); });
        tabPanels.forEach(function (p) { p.classList.toggle('hidden', p.dataset.panel !== id); });
        history.replaceState(null, '', '#' + id);
        try { localStorage.setItem('adminTab', id); } catch (e) {}
    }

    tabBtns.forEach(function (b) {
        b.addEventListener('click', function () { showTab(b.dataset.tab); });
    });

    var initTab = location.hash.slice(1);
    if (!validTabs.includes(initTab)) {
        try { initTab = localStorage.getItem('adminTab') || 'overview'; } catch (e) { initTab = 'overview'; }
    }
    showTab(initTab);

    // ── Modals ────────────────────────────────────────────────────
    window.adminOpenModal = function (id) {
        var el = document.getElementById(id);
        if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
    };

    window.adminCloseModal = function (id) {
        var el = document.getElementById(id);
        if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
    };

    document.querySelectorAll('.admin-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) { modal.classList.remove('open'); document.body.style.overflow = ''; }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.admin-modal.open').forEach(function (m) { m.classList.remove('open'); });
            document.body.style.overflow = '';
        }
    });

    // ── Collapsible create forms ──────────────────────────────────
    window.adminToggleCreate = function (id) {
        var body = document.getElementById(id);
        if (!body) return;
        var toggle = body.previousElementSibling;
        body.classList.toggle('open');
        if (toggle) toggle.classList.toggle('open');
    };

    // ── Cover preview ─────────────────────────────────────────────
    window.adminPreviewCover = function (imgId, url) {
        var img = document.getElementById(imgId);
        if (!img) return;
        var trimmed = url ? url.trim() : '';
        img.src = trimmed;
        img.classList.toggle('visible', trimmed !== '');
    };

}());
</script>
</body>
</html>
