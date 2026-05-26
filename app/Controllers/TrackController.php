<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Models\Like;
use App\Models\Playlist;
use App\Models\Track;

final class TrackController extends Controller
{
    public function show(Request $request): void
    {
        Session::start();

        $id    = (int) $request->input('id');
        $track = (new Track())->findDetailedById($id);

        if ($track === null) {
            http_response_code(404);
            echo 'Трек не найден';
            return;
        }

        $user          = Auth::user();
        $isLiked       = false;
        $userPlaylists = [];

        if ($user !== null) {
            $userId        = (int) $user['id'];
            $isLiked       = (new Like())->isLiked($userId, $id);
            $userPlaylists = (new Playlist())->findByUserId($userId);
        }

        $this->render('track/show', [
            'user'          => $user,
            'track'         => $track,
            'isLiked'       => $isLiked,
            'userPlaylists' => $userPlaylists,
        ]);
    }

    public function like(Request $request): void
    {
        Session::start();
        $user = Auth::user();

        if ($user === null) {
            $this->jsonError(401, 'Войди в аккаунт, чтобы ставить лайки.');
        }

        if (!RateLimiter::attempt(RateLimiter::userKey('like', (int) $user['id']), 60, 60)) {
            $this->jsonError(429, 'Слишком много запросов. Подожди немного.');
        }

        $trackId = (int) $request->input('track_id');

        if ($trackId <= 0 || (new Track())->findById($trackId) === null) {
            $this->jsonError(404, 'Трек не найден.');
        }

        $likeModel = new Like();
        $liked     = $likeModel->toggle((int) $user['id'], $trackId);
        $count     = $likeModel->countByTrack($trackId);

        $this->jsonOk(['liked' => $liked, 'count' => $count]);
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
