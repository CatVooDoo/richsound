<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\User;
use Throwable;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        Session::start();

        if (Auth::check()) {
            $this->redirectAfterLogin(Auth::user());
        }

        $this->render('auth/login', [
            'errors' => Session::consumeFlash('errors', []),
            'old' => Session::consumeFlash('old', []),
            'success' => Session::consumeFlash('success'),
        ]);
    }

    public function login(Request $request): void
    {
        Session::start();

        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Укажи email.';
        }

        if ($password === '') {
            $errors['password'] = 'Укажи пароль.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('old', ['email' => $email]);
            $this->redirect('/login');
        }

        try {
            $user = (new User())->findByEmail($email);
        } catch (Throwable) {
            Session::flash('errors', ['form' => 'Не удалось подключиться к базе. Проверь настройки MySQL.']);
            Session::flash('old', ['email' => $email]);
            $this->redirect('/login');
        }

        if ($user === null || !password_verify($password, $user['password'])) {
            Session::flash('errors', ['form' => 'Неверный email или пароль.']);
            Session::flash('old', ['email' => $email]);
            $this->redirect('/login');
        }

        Auth::login($user);
        Session::flash('success', 'Ты успешно вошел в аккаунт.');
        $this->redirectAfterLogin($user);
    }

    public function showRegister(Request $request): void
    {
        Session::start();

        if (Auth::check()) {
            $this->redirectAfterLogin(Auth::user());
        }

        $this->render('auth/register', [
            'errors' => Session::consumeFlash('errors', []),
            'old' => Session::consumeFlash('old', []),
        ]);
    }

    public function register(Request $request): void
    {
        Session::start();

        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $passwordConfirmation = (string) $request->input('password_confirmation');
        $role = (string) $request->input('role', 'listener');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Укажи имя.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Укажи корректный email.';
        }

        if (!in_array($role, ['listener', 'author'], true)) {
            $errors['role'] = 'Выбери допустимую роль.';
        }

        if (mb_strlen($password) < 6) {
            $errors['password'] = 'Пароль должен быть не короче 6 символов.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Пароли не совпадают.';
        }

        $userModel = new User();

        if ($email !== '' && $userModel->findByEmail($email) !== null) {
            $errors['email'] = 'Пользователь с таким email уже существует.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('old', [
                'name' => $name,
                'email' => $email,
                'role' => $role,
            ]);
            $this->redirect('/register');
        }

        try {
            $user = $userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
            ]);
        } catch (Throwable) {
            Session::flash('errors', ['form' => 'Не удалось создать пользователя. Проверь подключение к базе.']);
            Session::flash('old', [
                'name' => $name,
                'email' => $email,
                'role' => $role,
            ]);
            $this->redirect('/register');
        }

        Auth::login($user);
        Session::flash('success', 'Аккаунт создан. Добро пожаловать в Richsound.');
        $this->redirectAfterLogin($user);
    }

    public function logout(Request $request): void
    {
        Session::start();
        Auth::logout();
        Session::flash('success', 'Ты вышел из аккаунта.');
        $this->redirect('/');
    }

    /**
     * @param array<string, mixed>|null $user
     */
    private function redirectAfterLogin(?array $user): void
    {
        if (($user['role'] ?? null) === 'admin') {
            $this->redirect('/admin');
        }

        if (($user['role'] ?? null) === 'author') {
            $this->redirect('/author');
        }

        $this->redirect('/');
    }
}
