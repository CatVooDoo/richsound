<?php

declare(strict_types=1);

use App\Controllers\AlbumController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\AuthorController;
use App\Controllers\HomeController;
use App\Controllers\PlayerController;
use App\Controllers\PlaylistController;
use App\Controllers\TrackController;
use App\Controllers\UserController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/tracks/{id}', [TrackController::class, 'show']);
$router->get('/albums/{id}', [AlbumController::class, 'show']);
$router->get('/authors/{id}', [AuthorController::class, 'show']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/player/listens', [PlayerController::class, 'storeListen']);
$router->get('/player/stream', [PlayerController::class, 'stream']);
$router->get('/player/playlist', [PlayerController::class, 'playlist']);

$router->get('/author', [AuthorController::class, 'dashboard']);
$router->post('/author/tracks/upload', [AuthorController::class, 'uploadTrack']);
$router->post('/author/tracks/update', [AuthorController::class, 'updateTrack']);
$router->post('/author/tracks/delete', [AuthorController::class, 'deleteTrack']);
$router->post('/author/albums/create', [AuthorController::class, 'createAlbum']);
$router->post('/author/albums/update', [AuthorController::class, 'updateAlbum']);
$router->post('/author/albums/delete', [AuthorController::class, 'deleteAlbum']);

$router->post('/tracks/like', [TrackController::class, 'like']);

$router->get('/library', [UserController::class, 'library']);
$router->post('/authors/subscribe', [UserController::class, 'subscribe']);

$router->get('/playlists/{id}',           [PlaylistController::class, 'show']);
$router->post('/playlists/create',        [PlaylistController::class, 'create']);
$router->post('/playlists/delete',        [PlaylistController::class, 'delete']);
$router->post('/playlists/tracks/add',    [PlaylistController::class, 'addTrack']);
$router->post('/playlists/tracks/remove', [PlaylistController::class, 'removeTrack']);

$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/analytics', [AdminController::class, 'analytics']);
$router->post('/admin/users/create', [AdminController::class, 'createUser']);
$router->post('/admin/users/update', [AdminController::class, 'updateUser']);
$router->post('/admin/users/delete', [AdminController::class, 'deleteUser']);
$router->post('/admin/albums/create', [AdminController::class, 'createAlbum']);
$router->post('/admin/albums/update', [AdminController::class, 'updateAlbum']);
$router->post('/admin/albums/delete', [AdminController::class, 'deleteAlbum']);
$router->post('/admin/tracks/create', [AdminController::class, 'createTrack']);
$router->post('/admin/tracks/update', [AdminController::class, 'updateTrack']);
$router->post('/admin/tracks/delete', [AdminController::class, 'deleteTrack']);

$router->get('/podcasts',               [HomeController::class,   'podcasts']);
$router->get('/broadcasts',             [HomeController::class,   'broadcasts']);
$router->get('/search',                 [HomeController::class,   'search']);
$router->get('/search/suggest',         [HomeController::class,   'suggest']);
$router->get('/author/analytics/data',  [AuthorController::class, 'analyticsData']);
$router->post('/author/profile/update', [AuthorController::class, 'updateProfile']);
$router->post('/author/profile/avatar', [AuthorController::class, 'uploadAvatar']);
