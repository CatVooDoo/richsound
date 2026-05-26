<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Album;
use App\Models\Like;
use App\Models\Playlist;

final class AlbumController extends Controller
{
    public function show(Request $request): void
    {
        Session::start();

        $id    = (int) $request->input('id');
        $album = (new Album())->findPublicById($id);

        if ($album === null) {
            http_response_code(404);
            echo 'Альбом не найден';
            return;
        }

        $tracks = (new Album())->findTracksByAlbumId($id);
        $user   = Auth::user();

        $likedTrackIds = [];
        $userPlaylists = [];

        if ($user !== null) {
            $userId        = (int) $user['id'];
            $likedTrackIds = (new Like())->getTrackIdsByUser($userId);
            $userPlaylists = (new Playlist())->findByUserId($userId);
        }

        $this->render('album/show', [
            'user'          => $user,
            'album'         => $album,
            'tracks'        => $tracks,
            'likedTrackIds' => $likedTrackIds,
            'userPlaylists' => $userPlaylists,
        ]);
    }
}
