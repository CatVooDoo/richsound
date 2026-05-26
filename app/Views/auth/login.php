<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Richsound</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">
<main class="auth">
    <section class="auth__hero">
        <a class="auth__back" href="/">← На главную</a>
        <div class="auth__eyebrow">Доступ к Richsound</div>
        <h1 class="auth__title">Вход в аккаунт</h1>
        <p class="auth__description">Войди, чтобы открыть персональные подборки, плейлисты и историю прослушиваний.</p>
    </section>

    <section class="auth__panel">
        <a class="auth__back" href="/">← На главную</a>
        <div class="auth__eyebrow">Richsound</div>
        <h1 class="auth__title">Вход в аккаунт</h1>

        <?php if (!empty($success)): ?>
            <div class="auth__alert auth__alert--success"><?= htmlspecialchars((string) $success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['form'])): ?>
            <div class="auth__alert auth__alert--error"><?= htmlspecialchars((string) $errors['form']) ?></div>
        <?php endif; ?>

        <form class="auth-form" action="/login" method="post">
            <?= \App\Core\Csrf::field() ?>
            <div class="auth-form__group">
                <label class="auth-form__label" for="email">Email</label>
                <input class="auth-form__field" id="email" name="email" type="email" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="password">Пароль</label>
                <input class="auth-form__field" id="password" name="password" type="password" required>
                <?php if (!empty($errors['password'])): ?>
                    <p class="auth-form__error"><?= htmlspecialchars((string) $errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button class="auth-form__submit" type="submit">Войти</button>
        </form>

        <p class="auth__switch">
            Нет аккаунта?
            <a href="/register">Создать аккаунт</a>
        </p>
    </section>
</main>
</body>
</html>
