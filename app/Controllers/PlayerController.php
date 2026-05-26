<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Models\Listen;
use App\Models\Track;
use Throwable;


final class PlayerController extends Controller
{
    public function storeListen(Request $request): void
    {
        Session::start();

        header('Content-Type: application/json; charset=UTF-8');

        $user = Auth::user();
        $rlKey = $user !== null
            ? RateLimiter::userKey('listen', (int) $user['id'])
            : RateLimiter::ipKey('listen');

        if (!RateLimiter::attempt($rlKey, 30, 60)) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'message' => 'Слишком много запросов.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $trackId = (int) $request->input('track_id');
        $listenedSeconds = $request->input('listened_seconds');
        $normalizedSeconds = $listenedSeconds === null || $listenedSeconds === ''
            ? null
            : max(0, (int) $listenedSeconds);
        $isCompleted = in_array((string) $request->input('is_completed', '0'), ['1', 'true'], true);

        if ($trackId <= 0 || (new Track())->findById($trackId) === null) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Некорректный track_id'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            (new Listen())->create(
                $user !== null ? (int) $user['id'] : null,
                $trackId,
                $normalizedSeconds,
                $isCompleted
            );
        } catch (Throwable) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Не удалось сохранить прослушивание'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    public function stream(Request $request): void
    {
        Session::start();

        if (Auth::user() === null) {
            http_response_code(401);
            return;
        }

        $id = (int) $request->input('id');

        if ($id <= 0) {
            http_response_code(400);
            return;
        }

        $track = (new Track())->findById($id);

        if ($track === null) {
            http_response_code(404);
            return;
        }

        $filePath = BASE_PATH . '/public' . $track['file_path'];

        if (!is_file($filePath)) {
            http_response_code(404);
            return;
        }

        $size = filesize($filePath);
        $ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'ogg'  => 'audio/ogg',
            default => 'application/octet-stream',
        };

        $start      = 0;
        $end        = $size - 1;
        $statusCode = 200;

        $rangeHeader = $_SERVER['HTTP_RANGE'] ?? null;

        if ($rangeHeader !== null) {
            if (!preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
                header('Content-Range: bytes */' . $size);
                http_response_code(416);
                return;
            }

            if ($m[1] !== '') {
                $start = (int) $m[1];
                $end   = $m[2] !== '' ? (int) $m[2] : $size - 1;
            } else {
                $tail  = (int) $m[2];
                $start = max(0, $size - $tail);
                $end   = $size - 1;
            }

            $end = min($end, $size - 1);

            if ($start > $end || $start >= $size) {
                header('Content-Range: bytes */' . $size);
                http_response_code(416);
                return;
            }

            $statusCode = 206;
        }

        $length = $end - $start + 1;

        http_response_code($statusCode);
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $length);

        if ($statusCode === 206) {
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        }

        header('Content-Disposition: inline');
        header('Cache-Control: private, no-store');

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fp = fopen($filePath, 'rb');

        if ($fp === false) {
            error_log('[Richsound] Cannot open audio file: ' . $filePath);
            http_response_code(500);
            return;
        }

        $remaining = $length;
        $chunk     = 256 * 1024;

        fseek($fp, $start);

        while ($remaining > 0 && !feof($fp) && !connection_aborted()) {
            $read = (int) min($chunk, $remaining);
            echo fread($fp, $read);
            flush();
            $remaining -= $read;
        }

        fclose($fp);
    }

    public function playlist(Request $request): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: public, max-age=60');

        $trackModel = new Track();
        $latest     = $trackModel->findLatestForHome(8);
        $popular    = $trackModel->findPopularForHome(6);

        $seen     = [];
        $playlist = [];

        foreach (array_merge($latest, $popular) as $track) {
            $id = (int) ($track['id'] ?? 0);

            if ($id <= 0 || isset($seen[$id]) || ($track['file_path'] ?? '') === '') {
                continue;
            }

            $seen[$id]  = true;
            $coverPath  = trim((string) ($track['cover_path'] ?? ''));

            if ($coverPath !== '' && !str_starts_with($coverPath, '/') && !preg_match('~^https?://~i', $coverPath)) {
                $coverPath = '/' . ltrim($coverPath, '/');
            }

            $playlist[] = [
                'id'       => $id,
                'title'    => (string) ($track['title'] ?? ''),
                'artist'   => (string) ($track['author_name'] ?? ''),
                'album'    => (string) ($track['album_title'] ?? ''),
                'audioUrl' => '/player/stream?id=' . $id,
                'coverUrl' => $coverPath,
                'duration' => (int) ($track['duration'] ?? 0),
                'plays'    => (int) ($track['plays_count'] ?? 0),
                'likes'    => (int) ($track['likes_count'] ?? 0),
            ];
        }

        echo json_encode($playlist, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
