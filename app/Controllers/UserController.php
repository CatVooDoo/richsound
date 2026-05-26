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
use App\Models\Subscription;

final class UserController extends Controller
{
    public function library(Request $request): void
    {
        $user   = $this->requireUser();
        $userId = (int) $user['id'];

        $likedTracks   = (new Like())->findByUser($userId);
        $playlists     = (new Playlist())->findByUserId($userId);
        $subscriptions = (new Subscription())->findBySubscriber($userId);

        $this->render('user/library', [
            'user'          => $user,
            'likedTracks'   => $likedTracks,
            'playlists'     => $playlists,
            'subscriptions' => $subscriptions,
            'success'       => Session::consumeFlash('success'),
            'error'         => Session::consumeFlash('error'),
        ]);
    }

    public function subscribe(Request $request): void
    {
        Session::start();
        $user = Auth::user();

        if ($user === null) {
            $this->jsonError(401, 'Войди в аккаунт, чтобы подписываться на авторов.');
        }

        if (!RateLimiter::attempt(RateLimiter::userKey('subscribe', (int) $user['id']), 30, 60)) {
            $this->jsonError(429, 'Слишком много запросов. Подожди немного.');
        }

        $authorId = (int) $request->input('author_id');

        if ($authorId <= 0 || $authorId === (int) $user['id']) {
            $this->jsonError(400, 'Некорректный запрос.');
        }

        $subModel   = new Subscription();
        $subscribed = $subModel->toggle((int) $user['id'], $authorId);
        $count      = $subModel->countByAuthor($authorId);

        $this->jsonOk(['subscribed' => $subscribed, 'count' => $count]);
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
