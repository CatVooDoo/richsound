<?php

declare(strict_types=1);

namespace App\Core;

final class RedisConnection
{
    private static ?\Redis $instance = null;

    public static function get(): ?\Redis
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (!class_exists(\Redis::class)) {
            return null;
        }

        $host = (string) (getenv('REDIS_HOST') ?: 'redis');
        $port = (int) (getenv('REDIS_PORT') ?: 6379);

        try {
            $redis = new \Redis();
            $redis->connect($host, $port, 1.0);
            self::$instance = $redis;
        } catch (\Throwable) {
            return null;
        }

        return self::$instance;
    }
}
