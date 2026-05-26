<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Like;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;

final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        Session::start();

        $user       = Auth::user();
        $trackModel = new Track();

        $perPage     = 8;
        $page        = max(1, (int) $request->input('page', 1));
        $totalTracks = $trackModel->countAll();
        $totalPages  = (int) ceil($totalTracks / $perPage);
        $page        = min($page, max(1, $totalPages));

        $latestTracks  = $trackModel->findPaginatedForHome($perPage, ($page - 1) * $perPage);
        $popularTracks = $trackModel->findPopularForHome(6);
        $featuredTrack = $popularTracks[0] ?? $latestTracks[0] ?? null;

        $likedTrackIds = [];
        $userPlaylists = [];

        if ($user !== null) {
            $userId        = (int) $user['id'];
            $likedTrackIds = (new Like())->getTrackIdsByUser($userId);
            $userPlaylists = (new Playlist())->findByUserId($userId);
        }

        $this->render('home/index', [
            'user'          => $user,
            'success'       => Session::consumeFlash('success'),
            'error'         => Session::consumeFlash('error'),
            'featuredTrack' => $featuredTrack,
            'latestTracks'  => $latestTracks,
            'popularTracks' => $popularTracks,
            'likedTrackIds' => $likedTrackIds,
            'userPlaylists' => $userPlaylists,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalTracks'   => $totalTracks,
        ]);
    }

    public function search(Request $request): void
    {
        Session::start();

        $q       = trim((string) $request->input('q', ''));
        $user    = Auth::user();
        $tracks  = [];
        $authors = [];

        if ($q !== '') {
            $tracks  = (new Track())->search($q);
            $authors = (new User())->searchAuthors($q);
        }

        $likedTrackIds = [];
        $userPlaylists = [];

        if ($user !== null) {
            $userId        = (int) $user['id'];
            $likedTrackIds = (new Like())->getTrackIdsByUser($userId);
            $userPlaylists = (new Playlist())->findByUserId($userId);
        }

        $this->render('search/results', [
            'user'          => $user,
            'q'             => $q,
            'tracks'        => $tracks,
            'authors'       => $authors,
            'likedTrackIds' => $likedTrackIds,
            'userPlaylists' => $userPlaylists,
        ]);
    }

    public function podcasts(Request $request): void
    {
        Session::start();
        $this->render('home/podcasts', ['user' => Auth::user()]);
    }

    public function broadcasts(Request $request): void
    {
        Session::start();
        $this->render('home/broadcasts', ['user' => Auth::user()]);
    }

    public function suggest(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            echo json_encode(['tracks' => [], 'authors' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tracks  = (new Track())->search($q);
        $authors = (new User())->searchAuthors($q);

        $trackResults = array_map(static fn(array $t): array => [
            'id'          => (int) $t['id'],
            'title'       => (string) $t['title'],
            'author_name' => (string) ($t['author_name'] ?? ''),
            'cover_path'  => (string) ($t['cover_path'] ?? ''),
            'duration'    => (int) ($t['duration'] ?? 0),
        ], array_slice($tracks, 0, 5));

        $authorResults = array_map(static fn(array $a): array => [
            'id'                => (int) $a['id'],
            'name'              => (string) $a['name'],
            'avatar'            => (string) ($a['avatar'] ?? ''),
            'tracks_count'      => (int) ($a['tracks_count'] ?? 0),
            'subscribers_count' => (int) ($a['subscribers_count'] ?? 0),
        ], array_slice($authors, 0, 3));

        echo json_encode(
            ['tracks' => $trackResults, 'authors' => $authorResults],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
