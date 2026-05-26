<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Auth;
use App\Core\Session;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): void
    {
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found';
            return;
        }

        extract($data, EXTR_SKIP);

        require $viewPath;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireUser(): array
    {
        Session::start();

        $user = Auth::user();

        if ($user === null) {
            Session::flash('errors', ['form' => 'Сначала войди в аккаунт.']);
            $this->redirect('/login');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireAdmin(): array
    {
        $user = $this->requireUser();

        if (($user['role'] ?? null) !== 'admin') {
            Session::flash('error', 'У тебя нет доступа к панели администратора.');
            $this->redirect('/');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireAuthor(): array
    {
        $user = $this->requireUser();

        if (!\in_array($user['role'] ?? null, ['author', 'admin'], true)) {
            Session::flash('error', 'У тебя нет доступа к кабинету автора.');
            $this->redirect('/');
        }

        return $user;
    }
}
