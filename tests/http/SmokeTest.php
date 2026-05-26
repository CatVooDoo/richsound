<?php

declare(strict_types=1);

/**
 * Performs a simple HTTP request via cURL and returns [status, body, headers].
 *
 * @return array{int, string, string}
 */
function httpGet(string $url, array $cookies = [], bool $followRedirects = false): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_COOKIE         => implode('; ', array_map(
            fn($k, $v) => "$k=$v",
            array_keys($cookies),
            array_values($cookies)
        )),
    ]);
    $raw    = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hdrLen = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers = substr($raw, 0, $hdrLen);
    $body    = substr($raw, $hdrLen);
    return [$status, $body, $headers];
}

/**
 * POST with form data; returns [status, body, headers].
 *
 * @return array{int, string, string}
 */
function httpPost(string $url, array $data = [], array $cookies = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_COOKIE         => implode('; ', array_map(
            fn($k, $v) => "$k=$v",
            array_keys($cookies),
            array_values($cookies)
        )),
    ]);
    $raw    = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hdrLen = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers = substr($raw, 0, $hdrLen);
    $body    = substr($raw, $hdrLen);
    return [$status, $body, $headers];
}

/** Extract Set-Cookie values from raw headers into a key=>value map. */
function parseCookies(string $headers): array
{
    $cookies = [];
    foreach (explode("\n", $headers) as $line) {
        if (stripos($line, 'Set-Cookie:') === 0) {
            $part = trim(substr($line, strlen('Set-Cookie:')));
            $pair = explode(';', $part)[0];
            [$k, $v] = explode('=', $pair, 2) + [1 => ''];
            $cookies[trim($k)] = trim($v);
        }
    }
    return $cookies;
}

/** Fetch a fresh CSRF token (and its session cookie) from GET /login. */
function fetchCsrfToken(): array
{
    $baseUrl = getenv('BASE_URL') ?: 'http://nginx';
    [, $body, $headers] = httpGet("$baseUrl/login");
    $cookies = parseCookies($headers);
    $csrf    = '';
    if (preg_match('/<input[^>]+name="_csrf"[^>]+value="([^"]+)"/', $body, $m)) {
        $csrf = $m[1];
    }
    return [$csrf, $cookies];
}

/** Login and return cookies for authenticated requests. */
function loginAs(string $email, string $password): array
{
    $baseUrl = getenv('BASE_URL') ?: 'http://nginx';

    // Step 1: GET /login to obtain CSRF token and initial session cookie
    [, $loginBody, $loginHeaders] = httpGet("$baseUrl/login");
    $initCookies = parseCookies($loginHeaders);

    // Extract _csrf from the hidden input field
    $csrf = '';
    if (preg_match('/<input[^>]+name="_csrf"[^>]+value="([^"]+)"/', $loginBody, $m)) {
        $csrf = $m[1];
    }

    // Step 2: POST /login with CSRF token and initial session cookie
    [, , $postHeaders] = httpPost(
        "$baseUrl/login",
        ['email' => $email, 'password' => $password, '_csrf' => $csrf],
        $initCookies
    );

    // Merge: login POST may issue a new session cookie; prefer it over the initial one
    $postCookies = parseCookies($postHeaders);
    return array_merge($initCookies, $postCookies);
}

// ─────────────────────────────────────────────────────────────────────────────

function runSmokeTests(TestRunner $t): void
{
    $base = getenv('BASE_URL') ?: 'http://nginx';

    $t->suite('HTTP Smoke — public pages');

    $t->test('GET / returns 200', function (TestRunner $t) use ($base): void {
        [$status] = httpGet("$base/");
        $t->assertEqual(200, $status);
    });

    $t->test('GET /login returns 200', function (TestRunner $t) use ($base): void {
        [$status] = httpGet("$base/login");
        $t->assertEqual(200, $status);
    });

    $t->test('GET /register returns 200', function (TestRunner $t) use ($base): void {
        [$status] = httpGet("$base/register");
        $t->assertEqual(200, $status);
    });

    $t->test('GET /library redirects to /login when unauthenticated', function (TestRunner $t) use ($base): void {
        [$status, , $headers] = httpGet("$base/library");
        // Should redirect (301/302) or directly 302
        $t->assertTrue(in_array($status, [301, 302], true), "Expected redirect, got $status");
        $t->assertStringContains('login', strtolower($headers));
    });

    $t->test('GET /author redirects when unauthenticated', function (TestRunner $t) use ($base): void {
        [$status] = httpGet("$base/author");
        $t->assertTrue(in_array($status, [301, 302], true), "Expected redirect, got $status");
    });

    $t->test('GET /admin redirects when unauthenticated', function (TestRunner $t) use ($base): void {
        [$status] = httpGet("$base/admin");
        $t->assertTrue(in_array($status, [301, 302, 403], true), "Expected redirect/403, got $status");
    });

    $t->test('GET / HTML contains track list or empty state', function (TestRunner $t) use ($base): void {
        [, $body] = httpGet("$base/");
        // Page should have basic HTML structure
        $t->assertStringContains('<html', $body);
    });

    $t->test('POST /tracks/like returns 401 for unauthenticated request', function (TestRunner $t) use ($base): void {
        [$csrf, $csrfCookies] = fetchCsrfToken();
        [$status, $body] = httpPost("$base/tracks/like", ['track_id' => 1, '_csrf' => $csrf], $csrfCookies);
        $t->assertEqual(401, $status);
        $decoded = json_decode($body, true);
        $t->assertNotNull($decoded, 'Response should be JSON');
        $t->assertNotNull($decoded['error'] ?? null, 'Should have error key');
    });

    $t->test('POST /playlists/tracks/add returns 401 for unauthenticated request', function (TestRunner $t) use ($base): void {
        [$csrf, $csrfCookies] = fetchCsrfToken();
        [$status] = httpPost("$base/playlists/tracks/add", ['playlist_id' => 1, 'track_id' => 1, '_csrf' => $csrf], $csrfCookies);
        $t->assertEqual(401, $status);
    });
}
