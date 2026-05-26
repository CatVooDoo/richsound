<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | Richsound</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">
<main class="auth">
    <section class="auth__hero">
        <a class="auth__back" href="/">← На главную</a>
        <div class="auth__eyebrow">Присоединяйся к Richsound</div>
        <h1 class="auth__title">Создание аккаунта</h1>
        <p class="auth__description">Регистрируйся как слушатель или автор и начинай собирать собственную музыкальную экосистему.</p>
    </section>

    <section class="auth__panel">
        <a class="auth__back" href="/">← На главную</a>
        <div class="auth__eyebrow">Richsound</div>
        <h1 class="auth__title">Создание аккаунта</h1>

        <?php if (!empty($errors['form'])): ?>
            <div class="auth__alert auth__alert--error"><?= htmlspecialchars((string) $errors['form']) ?></div>
        <?php endif; ?>

        <form class="auth-form" action="/register" method="post">
            <?= \App\Core\Csrf::field() ?>
            <div class="auth-form__group">
                <label class="auth-form__label" for="name">Имя</label>
                <input class="auth-form__field" id="name" name="name" type="text" value="<?= htmlspecialchars((string) ($old['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="email">Email</label>
                <input class="auth-form__field" id="email" name="email" type="email" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="role">Роль</label>
                <select class="auth-form__field auth-form__field--select" id="role" name="role">
                    <option value="listener" <?= ($old['role'] ?? 'listener') === 'listener' ? 'selected' : '' ?>>Слушатель</option>
                    <option value="author" <?= ($old['role'] ?? '') === 'author' ? 'selected' : '' ?>>Автор</option>
                </select>
                <?php if (!empty($errors['role'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['role']) ?></p>
                <?php endif; ?>
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="password">Пароль</label>
                <input class="auth-form__field" id="password" name="password" type="password" required>
                <?php if (!empty($errors['password'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="password_confirmation">Подтверждение пароля</label>
                <input class="auth-form__field" id="password_confirmation" name="password_confirmation" type="password" required>
                <?php if (!empty($errors['password_confirmation'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['password_confirmation']) ?></p>
                <?php endif; ?>
            </div>

            <button class="auth-form__submit" type="submit">Создать аккаунт</button>
        </form>

        <p class="auth__switch">
            Уже есть аккаунт?
            <a href="/login">Войти</a>
        </p>
    </section>
</main>
</body>
</html>
