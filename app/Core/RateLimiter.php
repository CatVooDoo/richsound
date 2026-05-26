<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    /**
     * Returns true if the action is allowed, false if the limit is exceeded.
     *
     * Uses a sliding-window counter: INCR + EXPIRE (EXPIRE only on first hit
     * so the window resets from the first request, not each subsequent one).
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $redis = RedisConnection::get();

        if ($redis === null) {
            return true;
        }

        $count = $redis->incr($key);

        if ($count === 1) {
            $redis->expire($key, $decaySeconds);
        }

        return $count <= $maxAttempts;
    }

    public static function ipKey(string $route): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'unknown';

        $ip = explode(',', $ip)[0];

        return 'rl:' . $route . ':' . md5(trim($ip));
    }

    public static function userKey(string $route, int $userId): string
    {
        return 'rl:' . $route . ':u' . $userId;
    }
}
