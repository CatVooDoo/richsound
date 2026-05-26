<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Playlist;
use App\Models\Track;

final class PlaylistController extends Controller
{
    public function show(Request $request): void
    {
        Session::start();

        $playlistId    = (int) $request->input('id');
        $user          = Auth::user();
        $playlistModel = new Playlist();
        $playlist      = $playlistModel->findWithTracks($playlistId);

        if ($playlist === null) {
            http_response_code(404);
            echo 'Плейлист не найден';
            return;
        }

        $isOwner = $user !== null && (int) $playlist['user_id'] === (int) $user['id'];

        if (!(bool) $playlist['is_public'] && !$isOwner) {
            http_response_code(403);
            echo 'Нет доступа к плейлисту';
            return;
        }

        $tracks = $playlist['tracks'] ?? [];

        $playerPlaylist = array_values(array_map(static fn(array $t): array => [
            'id'       => (int) $t['id'],
            'title'    => (string) $t['title'],
            'artist'   => (string) ($t['author_name'] ?? ''),
            'album'    => (string) ($t['album_title'] ?? ''),
            'audioUrl' => '/player/stream?id=' . $t['id'],
            'coverUrl' => (string) ($t['cover_path'] ?? ''),
            'duration' => (int) ($t['duration'] ?? 0),
            'plays'    => (int) ($t['plays_count'] ?? 0),
            'likes'    => (int) ($t['likes_count'] ?? 0),
        ], array_filter($tracks, static fn(array $t): bool => ($t['file_path'] ?? '') !== '')));

        $this->render('user/playlists', [
            'user'           => $user,
            'playlist'       => $playlist,
            'tracks'         => $tracks,
            'isOwner'        => $isOwner,
            'playerPlaylist' => $playerPlaylist,
        ]);
    }

    public function create(Request $request): void
    {
        $user   = $this->requireUser();
        $userId = (int) $user['id'];
        $title  = trim((string) $request->input('title'));

        if ($title === '') {
            Session::flash('error', 'Укажи название плейлиста.');
            $this->redirect('/library');
        }

        $isPublic = $request->input('is_public') !== null;

        (new Playlist())->create($userId, $title, $isPublic);

        Session::flash('success', 'Плейлист «' . $title . '» создан.');
        $this->redirect('/library');
    }

    public function delete(Request $request): void
    {
        $user       = $this->requireUser();
        $userId     = (int) $user['id'];
        $playlistId = (int) $request->input('playlist_id');

        if ($playlistId <= 0) {
            Session::flash('error', 'Некорректный плейлист.');
            $this->redirect('/library');
        }

        $playlistModel = new Playlist();
        $playlist      = $playlistModel->findById($playlistId);

        if ($playlist === null || (int) $playlist['user_id'] !== $userId) {
            Session::flash('error', 'Плейлист не найден.');
            $this->redirect('/library');
        }

        $playlistModel->deleteById($playlistId);

        Session::flash('success', 'Плейлист удалён.');
        $this->redirect('/library');
    }

    public function addTrack(Request $request): void
    {
        Session::start();
        $user = Auth::user();

        if ($user === null) {
            $this->jsonError(401, 'Войди в аккаунт.');
        }

        $playlistId = (int) $request->input('playlist_id');
        $trackId    = (int) $request->input('track_id');

        if ($playlistId <= 0 || $trackId <= 0) {
            $this->jsonError(400, 'Некорректные данные.');
        }

        if ((new Track())->findById($trackId) === null) {
            $this->jsonError(404, 'Трек не найден.');
        }

        $playlistModel = new Playlist();
        $playlist      = $playlistModel->findById($playlistId);

        if ($playlist === null || (int) $playlist['user_id'] !== (int) $user['id']) {
            $this->jsonError(403, 'Плейлист не найден.');
        }

        $playlistModel->addTrack($playlistId, $trackId);

        $this->jsonOk(['added' => true, 'playlist' => $playlist['title']]);
    }

    public function removeTrack(Request $request): void
    {
        Session::start();
        $user = Auth::user();

        if ($user === null) {
            $this->jsonError(401, 'Войди в аккаунт.');
        }

        $playlistId = (int) $request->input('playlist_id');
        $trackId    = (int) $request->input('track_id');

        if ($playlistId <= 0 || $trackId <= 0) {
            $this->jsonError(400, 'Некорректные данные.');
        }

        $playlistModel = new Playlist();
        $playlist      = $playlistModel->findById($playlistId);

        if ($playlist === null || (int) $playlist['user_id'] !== (int) $user['id']) {
            $this->jsonError(403, 'Плейлист не найден.');
        }

        $playlistModel->removeTrack($playlistId, $trackId);

        $this->jsonOk(['removed' => true]);
    }

    private function jsonOk(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
