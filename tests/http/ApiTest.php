<?php

declare(strict_types=1);

function runApiTests(TestRunner $t): void
{
    $base = getenv('BASE_URL') ?: 'http://nginx';
    $ids  = seedFixtures();

    // Create a real user with known credentials for HTTP auth tests
    $db       = getTestDb();
    $suffix   = $ids['suffix'];
    $email    = "listener$suffix@test.loc";
    $password = 'secret';

    // Login as the seeded listener
    $cookies = loginAs($email, $password);
    $hasCookie = !empty($cookies);

    // Fetch CSRF token from an authenticated page for use in POST requests
    $csrf = '';
    if ($hasCookie) {
        [, $libraryBody] = httpGet("$base/library", $cookies);
        if (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/', $libraryBody, $m)) {
            $csrf = $m[1];
        }
    }

    $t->suite('HTTP API — authenticated endpoints');

    $t->test('Login succeeds and returns session cookie', function (TestRunner $t) use ($hasCookie, $cookies): void {
        $t->assertTrue($hasCookie, 'Expected session cookie after login. Cookies: ' . json_encode($cookies));
    });

    $t->test('GET /library returns 200 when authenticated', function (TestRunner $t) use ($base, $cookies): void {
        [$status] = httpGet("$base/library", $cookies);
        $t->assertEqual(200, $status);
    });

    // ── Like endpoints ────────────────────────────────────────────────────────

    $t->test('POST /tracks/like toggles like on (returns liked=true)', function (TestRunner $t) use ($base, $cookies, $ids, $csrf): void {
        [$status, $body] = httpPost("$base/tracks/like", ['track_id' => $ids['track_id'], '_csrf' => $csrf], $cookies);
        $t->assertEqual(200, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded, "Response must be JSON, got: $body");
        $t->assertTrue($decoded['liked'], 'First toggle should set liked=true');
        $t->assertGreaterThan(-1, (int) ($decoded['count'] ?? -1), 'count must be present');
    });

    $t->test('POST /tracks/like toggles like off (returns liked=false)', function (TestRunner $t) use ($base, $cookies, $ids, $csrf): void {
        [$status, $body] = httpPost("$base/tracks/like", ['track_id' => $ids['track_id'], '_csrf' => $csrf], $cookies);
        $t->assertEqual(200, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded);
        $t->assertFalse($decoded['liked'], 'Second toggle should set liked=false');
    });

    $t->test('POST /tracks/like with invalid track_id returns 404', function (TestRunner $t) use ($base, $cookies, $csrf): void {
        [$status, $body] = httpPost("$base/tracks/like", ['track_id' => 999999999, '_csrf' => $csrf], $cookies);
        $t->assertEqual(404, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded);
    });

    // ── Playlist endpoints ────────────────────────────────────────────────────

    $t->test('POST /playlists/create creates a playlist and redirects', function (TestRunner $t) use ($base, $cookies, $csrf): void {
        [$status, , $headers] = httpPost("$base/playlists/create", ['title' => 'HTTP Test Playlist', 'is_public' => '1', '_csrf' => $csrf], $cookies);
        // Should redirect to /library
        $t->assertTrue(in_array($status, [301, 302], true), "Expected redirect, got $status");
        $t->assertStringContains('library', strtolower($headers));
    });

    // Find the created playlist id
    $playlistId = 0;
    $t->test('Playlist appears in /library page', function (TestRunner $t) use ($base, $cookies, $ids, &$playlistId): void {
        [, $body] = httpGet("$base/library", $cookies);
        $t->assertStringContains('HTTP Test Playlist', $body);
        // Extract playlist id from a delete form action or data attribute
        if (preg_match('/playlist_id["\s=:]+(\d+)/', $body, $m)) {
            $playlistId = (int) $m[1];
        }
    });

    $t->test('POST /playlists/tracks/add adds track to playlist', function (TestRunner $t) use ($base, $cookies, $ids, $csrf, &$playlistId): void {
        if ($playlistId === 0) {
            // Fallback: find playlist via model
            $playlists = (new \App\Models\Playlist())->findByUserId($ids['listener_id']);
            $playlistId = (int) ($playlists[0]['id'] ?? 0);
        }
        if ($playlistId === 0) {
            throw new AssertionError('Cannot get playlist id — previous test may have failed');
        }
        [$status, $body] = httpPost("$base/playlists/tracks/add", [
            'playlist_id' => $playlistId,
            'track_id'    => $ids['track_id'],
            '_csrf'       => $csrf,
        ], $cookies);
        $t->assertEqual(200, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded, "Expected JSON, got: $body");
        $t->assertTrue($decoded['added'] ?? false, 'Response should confirm track added');
    });

    $t->test('POST /playlists/tracks/add is idempotent (no 500)', function (TestRunner $t) use ($base, $cookies, $ids, $csrf, &$playlistId): void {
        [$status] = httpPost("$base/playlists/tracks/add", [
            'playlist_id' => $playlistId,
            'track_id'    => $ids['track_id'],
            '_csrf'       => $csrf,
        ], $cookies);
        $t->assertTrue(in_array($status, [200, 409], true), "Expected 200 or 409, got $status");
    });

    $t->test('POST /playlists/tracks/remove removes track from playlist', function (TestRunner $t) use ($base, $cookies, $ids, $csrf, &$playlistId): void {
        [$status, $body] = httpPost("$base/playlists/tracks/remove", [
            'playlist_id' => $playlistId,
            'track_id'    => $ids['track_id'],
            '_csrf'       => $csrf,
        ], $cookies);
        $t->assertEqual(200, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded);
        $t->assertTrue($decoded['removed'] ?? false, 'Response should confirm removal');
    });

    $t->test('POST /playlists/tracks/add rejects wrong owner', function (TestRunner $t) use ($base, $ids, &$playlistId): void {
        // Login as author (different user) and fetch their CSRF token
        $authorEmail   = "author{$ids['suffix']}@test.loc";
        $authorCookies = loginAs($authorEmail, 'secret');
        $authorCsrf    = '';
        [, $authorPage] = httpGet((getenv('BASE_URL') ?: 'http://nginx') . '/author', $authorCookies);
        if (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/', $authorPage, $m)) {
            $authorCsrf = $m[1];
        }
        [$status] = httpPost(
            (getenv('BASE_URL') ?: 'http://nginx') . '/playlists/tracks/add',
            ['playlist_id' => $playlistId, 'track_id' => $ids['track_id'], '_csrf' => $authorCsrf],
            $authorCookies
        );
        $t->assertTrue(in_array($status, [403, 404], true), "Expected 403/404 for wrong owner, got $status");
    });

    // ── Subscribe endpoint ────────────────────────────────────────────────────

    $t->test('POST /authors/subscribe toggles subscription on', function (TestRunner $t) use ($base, $cookies, $ids, $csrf): void {
        [$status, $body] = httpPost("$base/authors/subscribe", ['author_id' => $ids['author_id'], '_csrf' => $csrf], $cookies);
        $t->assertEqual(200, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded, "Expected JSON, got: $body");
        $t->assertTrue($decoded['subscribed'], 'First call should set subscribed=true');
    });

    $t->test('POST /authors/subscribe toggles subscription off', function (TestRunner $t) use ($base, $cookies, $ids, $csrf): void {
        [$status, $body] = httpPost("$base/authors/subscribe", ['author_id' => $ids['author_id'], '_csrf' => $csrf], $cookies);
        $t->assertEqual(200, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded);
        $t->assertFalse($decoded['subscribed'], 'Second call should set subscribed=false');
    });

    $t->test('POST /authors/subscribe rejects self-subscription', function (TestRunner $t) use ($base, $ids): void {
        $authorEmail   = "author{$ids['suffix']}@test.loc";
        $authorCookies = loginAs($authorEmail, 'secret');
        $authorCsrf    = '';
        [, $authorPage] = httpGet((getenv('BASE_URL') ?: 'http://nginx') . '/author', $authorCookies);
        if (preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/', $authorPage, $m)) {
            $authorCsrf = $m[1];
        }
        [$status, $body] = httpPost(
            (getenv('BASE_URL') ?: 'http://nginx') . '/authors/subscribe',
            ['author_id' => $ids['author_id'], '_csrf' => $authorCsrf],
            $authorCookies
        );
        $t->assertEqual(400, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded);
        $t->assertNotNull($decoded['error'] ?? null);
    });

    // ── Cleanup ───────────────────────────────────────────────────────────────
    // Delete HTTP-created playlists before fixture cleanup
    if ($playlistId > 0) {
        (new \App\Models\Playlist())->deleteById($playlistId);
    }

    cleanFixtures($ids);
}
