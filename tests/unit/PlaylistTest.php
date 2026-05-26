<?php

declare(strict_types=1);

use App\Models\Playlist;

function runPlaylistTests(TestRunner $t): void
{
    $ids      = seedFixtures();
    $playlist = new Playlist();

    $t->suite('Playlist model');

    $playlistId = 0;

    $t->test('create returns a positive int id', function (TestRunner $t) use ($playlist, $ids, &$playlistId): void {
        $id = $playlist->create($ids['listener_id'], 'My Test Playlist');
        $t->assertGreaterThan(0, $id);
        $playlistId = $id;
    });

    $t->test('findById returns created playlist', function (TestRunner $t) use ($playlist, &$playlistId, $ids): void {
        $row = $playlist->findById($playlistId);
        $t->assertNotNull($row);
        $t->assertEqual('My Test Playlist', $row['title']);
        $t->assertEqual($ids['listener_id'], (int) $row['user_id']);
    });

    $t->test('findByUserId includes the new playlist', function (TestRunner $t) use ($playlist, $ids, &$playlistId): void {
        $rows = $playlist->findByUserId($ids['listener_id']);
        $ids2 = array_column($rows, 'id');
        $t->assertContains($playlistId, array_map('intval', $ids2));
    });

    $t->test('hasTrack returns false before adding', function (TestRunner $t) use ($playlist, &$playlistId, $ids): void {
        $t->assertFalse($playlist->hasTrack($playlistId, $ids['track_id']));
    });

    $t->test('addTrack succeeds', function (TestRunner $t) use ($playlist, &$playlistId, $ids): void {
        $playlist->addTrack($playlistId, $ids['track_id']);
        $t->assertTrue($playlist->hasTrack($playlistId, $ids['track_id']));
    });

    $t->test('addTrack is idempotent (no duplicate error)', function (TestRunner $t) use ($playlist, &$playlistId, $ids): void {
        $playlist->addTrack($playlistId, $ids['track_id']); // second call
        $withTracks = $playlist->findWithTracks($playlistId);
        $t->assertNotNull($withTracks);
        $t->assertCount(1, $withTracks['tracks']);
    });

    $t->test('findWithTracks returns playlist with tracks array', function (TestRunner $t) use ($playlist, &$playlistId, $ids): void {
        $row = $playlist->findWithTracks($playlistId);
        $t->assertNotNull($row);
        $t->assertNotNull($row['tracks'] ?? null);
        $trackIds = array_column($row['tracks'], 'id');
        $t->assertContains($ids['track_id'], array_map('intval', $trackIds));
    });

    $t->test('removeTrack removes track from playlist', function (TestRunner $t) use ($playlist, &$playlistId, $ids): void {
        $playlist->removeTrack($playlistId, $ids['track_id']);
        $t->assertFalse($playlist->hasTrack($playlistId, $ids['track_id']));
    });

    $t->test('findWithTracks returns empty tracks array after removal', function (TestRunner $t) use ($playlist, &$playlistId): void {
        $row = $playlist->findWithTracks($playlistId);
        $t->assertNotNull($row);
        $t->assertCount(0, $row['tracks']);
    });

    $t->test('updateById changes title', function (TestRunner $t) use ($playlist, &$playlistId): void {
        $playlist->updateById($playlistId, 'Renamed Playlist', false);
        $row = $playlist->findById($playlistId);
        $t->assertEqual('Renamed Playlist', $row['title']);
        $t->assertEqual('0', (string) $row['is_public']);
    });

    $t->test('deleteById removes playlist', function (TestRunner $t) use ($playlist, &$playlistId): void {
        $playlist->deleteById($playlistId);
        $row = $playlist->findById($playlistId);
        $t->assertNull($row);
    });

    $t->test('findById returns null for non-existent id', function (TestRunner $t) use ($playlist): void {
        $t->assertNull($playlist->findById(999999999));
    });

    cleanFixtures($ids);
}
