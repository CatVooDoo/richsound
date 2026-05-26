#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/TestRunner.php';

// HTTP helpers (needed by ApiTest even when running unit-only)
require __DIR__ . '/http/SmokeTest.php';

// Unit test files
require __DIR__ . '/unit/LikeTest.php';
require __DIR__ . '/unit/SubscriptionTest.php';
require __DIR__ . '/unit/PlaylistTest.php';
require __DIR__ . '/unit/TrackTest.php';

// HTTP API tests
require __DIR__ . '/http/ApiTest.php';

$t = new TestRunner();

// ── Establish DB connection early so models share one connection ─────────────
echo PHP_EOL . "  Connecting to database…";
try {
    getTestDb();
    echo " \033[32mOK\033[0m" . PHP_EOL;
} catch (Throwable $e) {
    echo " \033[31mFAILED: " . $e->getMessage() . "\033[0m" . PHP_EOL;
    exit(1);
}

// ── Unit tests ────────────────────────────────────────────────────────────────
runLikeTests($t);
runSubscriptionTests($t);
runPlaylistTests($t);
runTrackTests($t);

// ── HTTP tests ────────────────────────────────────────────────────────────────
$base = getenv('BASE_URL') ?: 'http://nginx';
echo PHP_EOL . "  HTTP target: \033[36m$base\033[0m" . PHP_EOL;

runSmokeTests($t);
runApiTests($t);

// ── Summary ───────────────────────────────────────────────────────────────────
$t->summary();

exit($t->hasFailed() ? 1 : 0);
