<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\ImageProcessor;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Models\Album;
use App\Models\Like;
use App\Models\Listen;
use App\Models\Playlist;
use App\Models\Subscription;
use App\Models\Track;
use App\Models\User;
use DateTimeImmutable;
use Throwable;

final class AuthorController extends Controller
{
    public function show(Request $request): void
    {
        Session::start();

        $id     = (int) $request->input('id');
        $author = (new User())->findPublicAuthorById($id);

        if ($author === null) {
            http_response_code(404);
            echo 'Автор не найден';
            return;
        }

        $trackModel = new Track();
        $albumModel = new Album();

        $tracks = $trackModel->findByAuthorIdWithStats($id);
        $albums = $albumModel->findByAuthorIdWithStats($id);

        $user         = Auth::user();
        $isSubscribed = false;

        if ($user !== null && (int) $user['id'] !== $id) {
            $isSubscribed = (new Subscription())->isSubscribed((int) $user['id'], $id);
        }

        $this->render('author/show', [
            'user'          => $user,
            'author'        => $author,
            'tracks'        => $tracks,
            'albums'        => $albums,
            'isSubscribed'  => $isSubscribed,
            'likedTrackIds' => $user !== null ? (new Like())->getTrackIdsByUser((int) $user['id']) : [],
            'userPlaylists' => $user !== null ? (new Playlist())->findByUserId((int) $user['id']) : [],
        ]);
    }

    public function dashboard(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];

        // Reload fresh user record so bio/avatar are always current
        $freshAuthor = (new User())->findById($authorId) ?? $author;

        $trackModel = new Track();
        $albumModel = new Album();

        $tracks       = $trackModel->findByAuthorIdWithStats($authorId);
        $albums       = $albumModel->findByAuthorIdWithStats($authorId);
        $albumOptions = $albumModel->findByAuthorIdForSelection($authorId);

        $totalPlays      = (int) array_sum(array_column($tracks, 'plays_count'));
        $totalListens    = (int) array_sum(array_column($tracks, 'listens_count'));
        $uniqueListeners = (new Listen())->getUniqueListenerCount($authorId);

        $this->render('author/dashboard', [
            'author'          => $freshAuthor,
            'tracks'          => $tracks,
            'albums'          => $albums,
            'albumOptions'    => $albumOptions,
            'totalPlays'      => $totalPlays,
            'totalListens'    => $totalListens,
            'uniqueListeners' => $uniqueListeners,
            'success'         => Session::consumeFlash('success'),
            'error'           => Session::consumeFlash('error'),
        ]);
    }

    public function uploadTrack(Request $request): void
    {
        $author = $this->requireAuthor();
        $authorId = (int) $author['id'];

        if (!RateLimiter::attempt(RateLimiter::userKey('upload', $authorId), 10, 3600)) {
            $this->cabinetError('Слишком много загрузок. Попробуй через час.');
        }

        $title = trim((string) $request->input('title'));
        $albumIdRaw = $request->input('album_id');
        $albumId = ($albumIdRaw === '' || $albumIdRaw === null) ? null : (int) $albumIdRaw;
        $durationRaw = (int) $request->input('duration');
        $duration = $durationRaw > 0 ? $durationRaw : null;

        $errors = [];

        if ($title === '') {
            $errors[] = 'Укажи название трека.';
        }

        $audioFile = $request->file('audio_file');

        if ($audioFile === null) {
            $errors[] = 'Выбери аудиофайл.';
        } elseif ($audioFile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->uploadErrorMessage($audioFile['error']);
        } else {
            $audioExt = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));

            if (!\in_array($audioExt, ['mp3', 'wav'], true)) {
                $errors[] = 'Допустимые форматы: MP3 и WAV.';
            }

            if ($audioFile['size'] > 50 * 1024 * 1024) {
                $errors[] = 'Размер файла не должен превышать 50 МБ.';
            }
        }

        if ($errors !== []) {
            $this->cabinetError(implode(' ', $errors));
        }

        /** @var array{name: string, type: string, tmp_name: string, error: int, size: int} $audioFile */
        $audioExt = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
        $audioName = bin2hex(random_bytes(16)) . '.' . $audioExt;
        $audioRelPath = '/uploads/tracks/' . $audioName;
        $audioAbsPath = BASE_PATH . '/public/uploads/tracks/' . $audioName;

        if (!move_uploaded_file($audioFile['tmp_name'], $audioAbsPath)) {
            $this->cabinetError('Не удалось сохранить аудиофайл. Проверь права на директорию.');
        }

        $coverRelPath = $this->handleCoverUpload($request);

        if ($albumId !== null) {
            $album = (new Album())->findById($albumId);

            if ($album === null || (int) $album['author_id'] !== $authorId) {
                $albumId = null;
            }
        }

        try {
            (new Track())->create([
                'author_id' => $authorId,
                'album_id' => $albumId,
                'title' => $title,
                'file_path' => $audioRelPath,
                'cover_path' => $coverRelPath,
                'duration' => $duration,
                'plays_count' => 0,
            ]);
        } catch (Throwable) {
            $this->safeUnlink($audioAbsPath);

            if ($coverRelPath !== null) {
                $this->safeUnlink(BASE_PATH . '/public' . $coverRelPath);
            }

            $this->cabinetError('Не удалось сохранить трек в базе данных.');
        }

        Session::flash('success', 'Трек «' . $title . '» успешно загружен.');
        $this->redirect('/author#tracks');
    }

    private function handleCoverUpload(Request $request): ?string
    {
        $coverFile = $request->file('cover_file');

        if ($coverFile === null || $coverFile['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($coverFile['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));

        if (!\in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        if ($coverFile['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $mime = mime_content_type($coverFile['tmp_name']);

        if (!\in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $name    = bin2hex(random_bytes(16)) . '.jpg';
        $absPath = BASE_PATH . '/public/uploads/covers/' . $name;

        $saved = ImageProcessor::resizeAndSave($coverFile['tmp_name'], $absPath, 600, 600);

        if (!$saved && !move_uploaded_file($coverFile['tmp_name'], $absPath)) {
            return null;
        }

        return '/uploads/covers/' . $name;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой.',
            UPLOAD_ERR_PARTIAL => 'Файл загружен не полностью.',
            default => 'Ошибка при загрузке файла.',
        };
    }

    public function updateTrack(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];

        $trackId = (int) $request->input('track_id');
        $title   = trim((string) $request->input('title'));

        if ($trackId <= 0 || $title === '') {
            $this->cabinetError('Некорректные данные формы.');
        }

        $trackModel = new Track();
        $track      = $trackModel->findById($trackId);

        if ($track === null || (int) $track['author_id'] !== $authorId) {
            $this->cabinetError('Трек не найден.');
        }

        $albumIdRaw = $request->input('album_id');
        $albumId    = ($albumIdRaw === '' || $albumIdRaw === null) ? null : (int) $albumIdRaw;

        if ($albumId !== null) {
            $album = (new Album())->findById($albumId);
            if ($album === null || (int) $album['author_id'] !== $authorId) {
                $albumId = null;
            }
        }

        $coverRelPath = $this->handleCoverUpload($request);

        if ($coverRelPath === null) {
            $coverRelPath = $track['cover_path'] ?: null;
        } else {
            if (!empty($track['cover_path'])) {
                $this->safeUnlink(BASE_PATH . '/public' . $track['cover_path']);
            }
        }

        $trackModel->updateById($trackId, [
            'author_id'   => $authorId,
            'album_id'    => $albumId,
            'title'       => $title,
            'file_path'   => $track['file_path'],
            'cover_path'  => $coverRelPath,
            'duration'    => $track['duration'] ?? null,
            'plays_count' => (int) ($track['plays_count'] ?? 0),
        ]);

        Session::flash('success', 'Трек «' . $title . '» обновлён.');
        $this->redirect('/author#tracks');
    }

    public function deleteTrack(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];
        $trackId  = (int) $request->input('track_id');

        if ($trackId <= 0) {
            $this->cabinetError('Некорректный идентификатор трека.');
        }

        $trackModel = new Track();
        $track      = $trackModel->findById($trackId);

        if ($track === null || (int) $track['author_id'] !== $authorId) {
            $this->cabinetError('Трек не найден.');
        }

        $trackModel->deleteById($trackId);

        if (!empty($track['file_path'])) {
            $this->safeUnlink(BASE_PATH . '/public' . $track['file_path']);
        }

        if (!empty($track['cover_path'])) {
            $this->safeUnlink(BASE_PATH . '/public' . $track['cover_path']);
        }

        Session::flash('success', 'Трек удалён.');
        $this->redirect('/author#tracks');
    }

    public function createAlbum(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];
        $title    = trim((string) $request->input('title'));

        if ($title === '') {
            Session::flash('error', 'Укажи название альбома.');
            $this->redirect('/author#albums');
        }

        $releasedAt   = $request->input('released_at') ?: null;
        $coverRelPath = $this->handleCoverUpload($request);

        (new Album())->create([
            'author_id'   => $authorId,
            'title'       => $title,
            'cover_path'  => $coverRelPath,
            'released_at' => $releasedAt,
        ]);

        Session::flash('success', 'Альбом «' . $title . '» создан.');
        $this->redirect('/author#albums');
    }

    public function updateAlbum(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];
        $albumId  = (int) $request->input('album_id');
        $title    = trim((string) $request->input('title'));

        if ($albumId <= 0 || $title === '') {
            Session::flash('error', 'Некорректные данные формы.');
            $this->redirect('/author#albums');
        }

        $albumModel = new Album();
        $album      = $albumModel->findById($albumId);

        if ($album === null || (int) $album['author_id'] !== $authorId) {
            Session::flash('error', 'Альбом не найден.');
            $this->redirect('/author#albums');
        }

        $coverRelPath = $this->handleCoverUpload($request);

        if ($coverRelPath === null) {
            $coverRelPath = $album['cover_path'] ?: null;
        } else {
            if (!empty($album['cover_path'])) {
                $this->safeUnlink(BASE_PATH . '/public' . $album['cover_path']);
            }
        }

        $releasedAt = $request->input('released_at') ?: null;

        $albumModel->updateById($albumId, [
            'author_id'   => $authorId,
            'title'       => $title,
            'cover_path'  => $coverRelPath,
            'released_at' => $releasedAt,
        ]);

        Session::flash('success', 'Альбом «' . $title . '» обновлён.');
        $this->redirect('/author#albums');
    }

    public function deleteAlbum(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];
        $albumId  = (int) $request->input('album_id');

        if ($albumId <= 0) {
            Session::flash('error', 'Некорректный идентификатор альбома.');
            $this->redirect('/author#albums');
        }

        $albumModel = new Album();
        $album      = $albumModel->findById($albumId);

        if ($album === null || (int) $album['author_id'] !== $authorId) {
            Session::flash('error', 'Альбом не найден.');
            $this->redirect('/author#albums');
        }

        $albumModel->deleteById($albumId);

        if (!empty($album['cover_path'])) {
            $this->safeUnlink(BASE_PATH . '/public' . $album['cover_path']);
        }

        Session::flash('success', 'Альбом удалён.');
        $this->redirect('/author#albums');
    }

    public function analyticsData(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];
        $days     = max(1, min(90, (int) $request->input('period', 7)));

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $listenModel    = new Listen();
        $subModel       = new Subscription();
        $listenRows     = $listenModel->getAuthorStats($authorId, $days);
        $uniqueRows     = $listenModel->getUniqueListenersByDay($authorId, $days);
        $topTracks      = $listenModel->getTopByAuthor($authorId, 5);
        $completionRate = $listenModel->getCompletionRate($authorId);
        $totalUnique    = $listenModel->getUniqueListenerCount($authorId);
        $subRows        = $subModel->getDynamics($authorId, $days);

        echo json_encode([
            'listens'              => $this->normalizeAuthorSeries($listenRows, $days),
            'uniqueListeners'      => $this->normalizeAuthorSeries($uniqueRows, $days),
            'totalUniqueListeners' => $totalUnique,
            'topTracks'            => $topTracks,
            'completionRate'       => $completionRate,
            'subscribers'          => $this->normalizeAuthorSeries($subRows, $days),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function updateProfile(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];

        // Reload from DB to get current password hash (session may be stale)
        $freshUser = (new User())->findById($authorId);

        if ($freshUser === null) {
            $this->redirect('/login');
        }

        $name = trim((string) $request->input('name'));
        $bio  = trim((string) $request->input('bio'));
        $bio  = $bio === '' ? null : $bio;

        if ($name === '') {
            Session::flash('error', 'Имя не может быть пустым.');
            $this->redirect('/author#profile');
        }

        if (mb_strlen($name) > 255) {
            Session::flash('error', 'Имя слишком длинное (максимум 255 символов).');
            $this->redirect('/author#profile');
        }

        $currentPassword = (string) $request->input('current_password', '');
        $newPassword     = (string) $request->input('new_password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');

        if ($newPassword !== '') {
            if (!password_verify($currentPassword, (string) $freshUser['password'])) {
                Session::flash('error', 'Неверный текущий пароль.');
                $this->redirect('/author#profile');
            }

            if (mb_strlen($newPassword) < 6) {
                Session::flash('error', 'Новый пароль должен содержать не менее 6 символов.');
                $this->redirect('/author#profile');
            }

            if ($newPassword !== $confirmPassword) {
                Session::flash('error', 'Пароли не совпадают.');
                $this->redirect('/author#profile');
            }
        }

        (new User())->updateProfile($authorId, [
            'name'         => $name,
            'bio'          => $bio,
            'new_password' => $newPassword !== '' ? $newPassword : null,
        ]);

        Session::flash('success', 'Профиль обновлён.');
        $this->redirect('/author#profile');
    }

    public function uploadAvatar(Request $request): void
    {
        $author   = $this->requireAuthor();
        $authorId = (int) $author['id'];

        $file = $request->file('avatar_file');

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Выбери файл изображения.');
            $this->redirect('/author#profile');
        }

        /** @var array{name: string, type: string, tmp_name: string, error: int, size: int} $file */
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!\in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            Session::flash('error', 'Допустимые форматы: JPG, PNG, WEBP.');
            $this->redirect('/author#profile');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            Session::flash('error', 'Размер файла не должен превышать 5 МБ.');
            $this->redirect('/author#profile');
        }

        $mime = mime_content_type($file['tmp_name']);

        if (!\in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            Session::flash('error', 'Недопустимый тип файла.');
            $this->redirect('/author#profile');
        }

        $name    = bin2hex(random_bytes(16)) . '.jpg';
        $absPath = BASE_PATH . '/public/uploads/avatars/' . $name;

        $saved = ImageProcessor::resizeAndSave($file['tmp_name'], $absPath, 300, 300);

        if (!$saved && !move_uploaded_file($file['tmp_name'], $absPath)) {
            Session::flash('error', 'Не удалось сохранить аватар.');
            $this->redirect('/author#profile');
        }

        // Reload fresh record to get current avatar path
        $freshUser  = (new User())->findById($authorId);
        $oldAvatar  = (string) ($freshUser['avatar'] ?? '');

        if ($oldAvatar !== '' && str_starts_with($oldAvatar, '/uploads/avatars/')) {
            $this->safeUnlink(BASE_PATH . '/public' . $oldAvatar);
        }

        (new User())->updateAvatar($authorId, '/uploads/avatars/' . $name);

        Session::flash('success', 'Аватар обновлён.');
        $this->redirect('/author#profile');
    }

    /**
     * Fills date gaps with zeros so charts always have N data points.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAuthorSeries(array $rows, int $days): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(string) $row['day']] = (int) $row['total'];
        }

        $result = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date     = new DateTimeImmutable('-' . $offset . ' days');
            $key      = $date->format('Y-m-d');
            $result[] = [
                'date'  => $key,
                'label' => $date->format('d.m'),
                'count' => $indexed[$key] ?? 0,
            ];
        }

        return $result;
    }

    private function safeUnlink(string $path): void
    {
        if (!unlink($path)) {
            error_log('[Richsound] Failed to delete file: ' . $path);
        }
    }

    private function cabinetError(string $message): never
    {
        Session::flash('error', $message);
        $this->redirect('/author#tracks');
    }
}
