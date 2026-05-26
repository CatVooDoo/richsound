<?php

declare(strict_types=1);

use App\Models\Like;

function runLikeTests(TestRunner $t): void
{
    $ids  = seedFixtures();
    $like = new Like();

    $t->suite('Like model');

    $t->test('isLiked returns false before any like', function (TestRunner $t) use ($like, $ids): void {
        $t->assertFalse($like->isLiked($ids['listener_id'], $ids['track_id']));
    });

    $t->test('toggle returns true (liked) on first call', function (TestRunner $t) use ($like, $ids): void {
        $result = $like->toggle($ids['listener_id'], $ids['track_id']);
        $t->assertTrue($result);
    });

    $t->test('isLiked returns true after toggle', function (TestRunner $t) use ($like, $ids): void {
        $t->assertTrue($like->isLiked($ids['listener_id'], $ids['track_id']));
    });

    $t->test('countByTrack is 1 after one like', function (TestRunner $t) use ($like, $ids): void {
        $t->assertEqual(1, $like->countByTrack($ids['track_id']));
    });

    $t->test('toggle returns false (unliked) on second call', function (TestRunner $t) use ($like, $ids): void {
        $result = $like->toggle($ids['listener_id'], $ids['track_id']);
        $t->assertFalse($result);
    });

    $t->test('isLiked returns false after second toggle', function (TestRunner $t) use ($like, $ids): void {
        $t->assertFalse($like->isLiked($ids['listener_id'], $ids['track_id']));
    });

    $t->test('countByTrack is 0 after unlike', function (TestRunner $t) use ($like, $ids): void {
        $t->assertEqual(0, $like->countByTrack($ids['track_id']));
    });

    $t->test('getTrackIdsByUser returns liked track ids', function (TestRunner $t) use ($like, $ids): void {
        $like->toggle($ids['listener_id'], $ids['track_id']); // like again
        $ids2 = $like->getTrackIdsByUser($ids['listener_id']);
        $t->assertContains($ids['track_id'], $ids2);
    });

    $t->test('getTrackIdsByUser excludes other users tracks', function (TestRunner $t) use ($like, $ids): void {
        $ids2 = $like->getTrackIdsByUser($ids['author_id']);
        $t->assertNotContains($ids['track_id'], $ids2);
    });

    $t->test('findByUser returns enriched rows', function (TestRunner $t) use ($like, $ids): void {
        $rows = $like->findByUser($ids['listener_id']);
        $t->assertGreaterThan(0, count($rows));
        $trackIds = array_column($rows, 'id');
        $t->assertContains($ids['track_id'], $trackIds);
        // Check enrichment columns exist
        $row = $rows[array_search($ids['track_id'], $trackIds)];
        $t->assertNotNull($row['author_name'] ?? null, 'author_name must be present');
    });

    cleanFixtures($ids);
}
