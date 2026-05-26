<?php

declare(strict_types=1);

use App\Models\Track;

function runTrackTests(TestRunner $t): void
{
    $ids   = seedFixtures();
    $track = new Track();

    $t->suite('Track model');

    $t->test('findById returns the seeded track', function (TestRunner $t) use ($track, $ids): void {
        $row = $track->findById($ids['track_id']);
        $t->assertNotNull($row);
        $t->assertEqual($ids['track_id'], (int) $row['id']);
    });

    $t->test('findById returns null for non-existent id', function (TestRunner $t) use ($track): void {
        $t->assertNull($track->findById(999999999));
    });

    $t->test('findDetailedById returns enriched row', function (TestRunner $t) use ($track, $ids): void {
        $row = $track->findDetailedById($ids['track_id']);
        $t->assertNotNull($row);
        $t->assertNotNull($row['author_name'] ?? null, 'author_name must be present');
        $t->assertNotNull($row['likes_count'] ?? null, 'likes_count must be present');
        $t->assertNotNull($row['listens_count'] ?? null, 'listens_count must be present');
    });

    $t->test('findLatestForHome includes the seeded track', function (TestRunner $t) use ($track, $ids): void {
        $rows = $track->findLatestForHome(50);
        $ids2 = array_map('intval', array_column($rows, 'id'));
        $t->assertContains($ids['track_id'], $ids2);
    });

    $t->test('findPopularForHome includes the seeded track', function (TestRunner $t) use ($track, $ids): void {
        $rows = $track->findPopularForHome(50);
        $ids2 = array_map('intval', array_column($rows, 'id'));
        $t->assertContains($ids['track_id'], $ids2);
    });

    $t->test('findByAuthorIdWithStats returns tracks for author', function (TestRunner $t) use ($track, $ids): void {
        $rows  = $track->findByAuthorIdWithStats($ids['author_id']);
        $ids2  = array_map('intval', array_column($rows, 'id'));
        $t->assertContains($ids['track_id'], $ids2);
    });

    $t->test('deleteById removes the track', function (TestRunner $t) use ($track, $ids): void {
        // Create a throwaway track so the fixture track stays intact
        $db = getTestDb();
        $db->prepare('INSERT INTO tracks (author_id, album_id, title, file_path) VALUES (:a,:al,:t,:f)')
           ->execute(['a' => $ids['author_id'], 'al' => $ids['album_id'], 't' => 'Temp track', 'f' => 'fake/tmp.mp3']);
        $tmpId = (int) $db->lastInsertId();

        $track->deleteById($tmpId);
        $t->assertNull($track->findById($tmpId));
    });

    cleanFixtures($ids);
}
