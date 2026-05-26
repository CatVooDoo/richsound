<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\AdminDashboard;
use App\Models\Album;
use App\Models\Track;
use App\Models\User;
use DateTimeImmutable;
use Throwable;

final class AdminController extends Controller
{
    public function index(Request $request): void
    {
        $admin = $this->requireAdmin();

        $dashboard = new AdminDashboard();
        $userModel = new User();
        $albumModel = new Album();
        $trackModel = new Track();

        $this->render('admin/index', [
            'admin'             => $admin,
            'summary'           => $dashboard->summary(),
            'users'             => $userModel->findAllWithStats(),
            'authors'           => $userModel->findAuthorsForSelection(),
            'albums'            => $albumModel->findAllWithStats(),
            'albumOptions'      => $albumModel->findAllForSelection(),
            'tracks'            => $trackModel->findAllWithStats(),
            'listenChart'       => $this->normalizeDailySeries($dashboard->listensByDay(7), 7),
            'registrationChart' => $this->normalizeDailySeries($dashboard->registrationsByDay(7), 7),
            'topTracks'         => $dashboard->topTracksByPeriod(7, 5),
            'topAuthors'        => $dashboard->topAuthors(5),
            'success'           => Session::consumeFlash('success'),
            'error'             => Session::consumeFlash('error'),
        ]);
    }

    public function createUser(Request $request): void
    {
        $this->requireAdmin();

        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $role = (string) $request->input('role', 'listener');
        $password = (string) $request->input('password');

        $errors = $this->validateUserPayload($name, $email, $role, true, $password);
        $userModel = new User();

        if ($email !== '' && $userModel->findByEmail($email) !== null) {
            $errors[] = 'Пользователь с таким email уже существует.';
        }

        if ($errors !== []) {
            $this->dashboardError(implode(' ', $errors));
        }

        try {
            $userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
            ]);
        } catch (Throwable) {
            $this->dashboardError('Не удалось создать пользователя.');
        }

        $this->dashboardSuccess('Пользователь создан.');
    }

    public function updateUser(Request $request): void
    {
        $admin = $this->requireAdmin();

        $id = (int) $request->input('id');
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $role = (string) $request->input('role', 'listener');
        $password = (string) $request->input('password');

        $userModel = new User();
        $user = $userModel->findById($id);

        if ($user === null) {
            $this->dashboardError('Пользователь не найден.');
        }

        $errors = $this->validateUserPayload($name, $email, $role, false, $password);
        $existing = $email !== '' ? $userModel->findByEmail($email) : null;

        if ($existing !== null && (int) $existing['id'] !== $id) {
            $errors[] = 'Этот email уже занят.';
        }

        if ((int) $admin['id'] === $id && $role !== 'admin' && $userModel->countAdmins() <= 1) {
            $errors[] = 'Нельзя снять роль у последнего администратора.';
        }

        if ($errors !== []) {
            $this->dashboardError(implode(' ', $errors));
        }

        try {
            $userModel->updateById($id, [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'password' => $password,
            ]);
        } catch (Throwable) {
            $this->dashboardError('Не удалось обновить пользователя.');
        }

        $this->dashboardSuccess('Пользователь обновлён.');
    }

    public function deleteUser(Request $request): void
    {
        $admin = $this->requireAdmin();
        $id = (int) $request->input('id');

        $userModel = new User();
        $user = $userModel->findById($id);

        if ($user === null) {
            $this->dashboardError('Пользователь не найден.');
        }

        if ((int) $admin['id'] === $id) {
            $this->dashboardError('Нельзя удалить текущего администратора из активной сессии.');
        }

        if (($user['role'] ?? null) === 'admin' && $userModel->countAdmins() <= 1) {
            $this->dashboardError('Нельзя удалить последнего администратора.');
        }

        try {
            $userModel->deleteById($id);
        } catch (Throwable) {
            $this->dashboardError('Не удалось удалить пользователя.');
        }

        $this->dashboardSuccess('Пользователь удалён.');
    }

    public function createAlbum(Request $request): void
    {
        $this->requireAdmin();

        $payload = $this->validateAlbumRequest($request);

        if ($payload['errors'] !== []) {
            $this->dashboardError(implode(' ', $payload['errors']));
        }

        try {
            (new Album())->create($payload['data']);
        } catch (Throwable) {
            $this->dashboardError('Не удалось создать альбом.');
        }

        $this->dashboardSuccess('Альбом создан.');
    }

    public function updateAlbum(Request $request): void
    {
        $this->requireAdmin();

        $id = (int) $request->input('id');
        $albumModel = new Album();

        if ($albumModel->findById($id) === null) {
            $this->dashboardError('Альбом не найден.');
        }

        $payload = $this->validateAlbumRequest($request);

        if ($payload['errors'] !== []) {
            $this->dashboardError(implode(' ', $payload['errors']));
        }

        try {
            $albumModel->updateById($id, $payload['data']);
        } catch (Throwable) {
            $this->dashboardError('Не удалось обновить альбом.');
        }

        $this->dashboardSuccess('Альбом обновлён.');
    }

    public function deleteAlbum(Request $request): void
    {
        $this->requireAdmin();
        $id = (int) $request->input('id');

        $albumModel = new Album();

        if ($albumModel->findById($id) === null) {
            $this->dashboardError('Альбом не найден.');
        }

        try {
            $albumModel->deleteById($id);
        } catch (Throwable) {
            $this->dashboardError('Не удалось удалить альбом.');
        }

        $this->dashboardSuccess('Альбом удалён.');
    }

    public function createTrack(Request $request): void
    {
        $this->requireAdmin();

        $payload = $this->validateTrackRequest($request);

        if ($payload['errors'] !== []) {
            $this->dashboardError(implode(' ', $payload['errors']));
        }

        try {
            (new Track())->create($payload['data']);
        } catch (Throwable) {
            $this->dashboardError('Не удалось создать трек.');
        }

        $this->dashboardSuccess('Трек создан.');
    }

    public function updateTrack(Request $request): void
    {
        $this->requireAdmin();
        $id = (int) $request->input('id');

        $trackModel = new Track();

        if ($trackModel->findById($id) === null) {
            $this->dashboardError('Трек не найден.');
        }

        $payload = $this->validateTrackRequest($request);

        if ($payload['errors'] !== []) {
            $this->dashboardError(implode(' ', $payload['errors']));
        }

        try {
            $trackModel->updateById($id, $payload['data']);
        } catch (Throwable) {
            $this->dashboardError('Не удалось обновить трек.');
        }

        $this->dashboardSuccess('Трек обновлён.');
    }

    public function deleteTrack(Request $request): void
    {
        $this->requireAdmin();
        $id = (int) $request->input('id');

        $trackModel = new Track();

        if ($trackModel->findById($id) === null) {
            $this->dashboardError('Трек не найден.');
        }

        try {
            $trackModel->deleteById($id);
        } catch (Throwable) {
            $this->dashboardError('Не удалось удалить трек.');
        }

        $this->dashboardSuccess('Трек удалён.');
    }

    public function analytics(Request $request): void
    {
        $this->requireAdmin();
        $period = max(1, min(90, (int) $request->input('period', 7)));
        $dashboard = new AdminDashboard();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'listens'       => $this->normalizeDailySeries($dashboard->listensByDay($period), $period),
            'registrations' => $this->normalizeDailySeries($dashboard->registrationsByDay($period), $period),
            'topTracks'     => $dashboard->topTracksByPeriod($period, 5),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{data: array<string, mixed>, errors: array<int, string>}
     */
    private function validateAlbumRequest(Request $request): array
    {
        $authorId = (int) $request->input('author_id');
        $title = trim((string) $request->input('title'));
        $coverPath = $this->normalizeNullableString($request->input('cover_path'));
        $releasedAt = $this->normalizeNullableString($request->input('released_at'));

        $errors = [];

        if ($title === '') {
            $errors[] = 'Укажи название альбома.';
        }

        $author = (new User())->findById($authorId);

        if ($author === null || !in_array((string) $author['role'], ['author', 'admin'], true)) {
            $errors[] = 'Выбери пользователя с ролью автора или администратора.';
        }

        return [
            'data' => [
                'author_id' => $authorId,
                'title' => $title,
                'cover_path' => $coverPath,
                'released_at' => $releasedAt,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * @return array{data: array<string, mixed>, errors: array<int, string>}
     */
    private function validateTrackRequest(Request $request): array
    {
        $authorId = (int) $request->input('author_id');
        $albumIdRaw = $request->input('album_id');
        $albumId = $albumIdRaw === '' || $albumIdRaw === null ? null : (int) $albumIdRaw;
        $title = trim((string) $request->input('title'));
        $filePath = trim((string) $request->input('file_path'));
        $coverPath = $this->normalizeNullableString($request->input('cover_path'));
        $durationRaw = $request->input('duration');
        $duration = $durationRaw === '' || $durationRaw === null ? null : max(0, (int) $durationRaw);
        $playsCount = max(0, (int) $request->input('plays_count', 0));

        $errors = [];

        if ($title === '') {
            $errors[] = 'Укажи название трека.';
        }

        if ($filePath === '') {
            $errors[] = 'Укажи путь к аудиофайлу.';
        }

        $author = (new User())->findById($authorId);

        if ($author === null || !in_array((string) $author['role'], ['author', 'admin'], true)) {
            $errors[] = 'Выбери пользователя с ролью автора или администратора.';
        }

        if ($albumId !== null && (new Album())->findById($albumId) === null) {
            $errors[] = 'Выбранный альбом не существует.';
        }

        return [
            'data' => [
                'author_id' => $authorId,
                'album_id' => $albumId,
                'title' => $title,
                'file_path' => $filePath,
                'cover_path' => $coverPath,
                'duration' => $duration,
                'plays_count' => $playsCount,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function validateUserPayload(
        string $name,
        string $email,
        string $role,
        bool $passwordRequired,
        string $password
    ): array {
        $errors = [];

        if ($name === '') {
            $errors[] = 'Укажи имя пользователя.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Укажи корректный email.';
        }

        if (!in_array($role, ['listener', 'author', 'admin'], true)) {
            $errors[] = 'Выбери корректную роль.';
        }

        if ($passwordRequired && mb_strlen($password) < 6) {
            $errors[] = 'Пароль должен быть не короче 6 символов.';
        }

        if (!$passwordRequired && $password !== '' && mb_strlen($password) < 6) {
            $errors[] = 'Новый пароль должен быть не короче 6 символов.';
        }

        return $errors;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDailySeries(array $rows, int $days): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(string) $row['day']] = (int) $row['total'];
        }

        $result = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = new DateTimeImmutable('-' . $offset . ' days');
            $key = $date->format('Y-m-d');
            $result[] = [
                'date' => $key,
                'label' => $date->format('d.m'),
                'total' => $indexed[$key] ?? 0,
            ];
        }

        return $result;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function dashboardSuccess(string $message): void
    {
        Session::flash('success', $message);
        $this->redirect('/admin');
    }

    private function dashboardError(string $message): void
    {
        Session::flash('error', $message);
        $this->redirect('/admin');
    }
}
