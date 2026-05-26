<?php

declare(strict_types=1);

use App\Models\Subscription;

function runSubscriptionTests(TestRunner $t): void
{
    $ids  = seedFixtures();
    $sub  = new Subscription();

    $t->suite('Subscription model');

    $t->test('isSubscribed returns false initially', function (TestRunner $t) use ($sub, $ids): void {
        $t->assertFalse($sub->isSubscribed($ids['listener_id'], $ids['author_id']));
    });

    $t->test('toggle returns true (subscribed) on first call', function (TestRunner $t) use ($sub, $ids): void {
        $result = $sub->toggle($ids['listener_id'], $ids['author_id']);
        $t->assertTrue($result);
    });

    $t->test('isSubscribed returns true after toggle', function (TestRunner $t) use ($sub, $ids): void {
        $t->assertTrue($sub->isSubscribed($ids['listener_id'], $ids['author_id']));
    });

    $t->test('countByAuthor is 1 after one subscription', function (TestRunner $t) use ($sub, $ids): void {
        $t->assertEqual(1, $sub->countByAuthor($ids['author_id']));
    });

    $t->test('toggle returns false (unsubscribed) on second call', function (TestRunner $t) use ($sub, $ids): void {
        $result = $sub->toggle($ids['listener_id'], $ids['author_id']);
        $t->assertFalse($result);
    });

    $t->test('isSubscribed returns false after second toggle', function (TestRunner $t) use ($sub, $ids): void {
        $t->assertFalse($sub->isSubscribed($ids['listener_id'], $ids['author_id']));
    });

    $t->test('countByAuthor is 0 after unsubscribe', function (TestRunner $t) use ($sub, $ids): void {
        $t->assertEqual(0, $sub->countByAuthor($ids['author_id']));
    });

    $t->test('findBySubscriber returns subscribed authors', function (TestRunner $t) use ($sub, $ids): void {
        $sub->toggle($ids['listener_id'], $ids['author_id']); // re-subscribe
        $rows = $sub->findBySubscriber($ids['listener_id']);
        $authorIds = array_column($rows, 'id');
        $t->assertContains($ids['author_id'], $authorIds);
        // Verify aggregate columns present
        $row = $rows[array_search($ids['author_id'], $authorIds)];
        $t->assertNotNull($row['subscribers_count'] ?? null);
        $t->assertNotNull($row['tracks_count'] ?? null);
    });

    $t->test('findBySubscriber excludes authors not followed', function (TestRunner $t) use ($sub, $ids): void {
        // listener_id is not an author anyone follows; check we only get author_id
        $rows = $sub->findBySubscriber($ids['author_id']);
        $authorIds = array_column($rows, 'id');
        $t->assertNotContains($ids['author_id'], $authorIds, 'Author should not follow themselves');
    });

    cleanFixtures($ids);
}
