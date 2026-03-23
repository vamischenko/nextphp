<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use Redis;

final class CacheFactory
{
    public static function array(): ArrayCache
    {
        return new ArrayCache();
    }

    public static function file(string $directory): FileCache
    {
        return new FileCache($directory);
    }

    public static function redis(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $prefix = 'nextphp:',
        int $database = 0,
    ): RedisCache {
        $redis = new Redis();
        $redis->connect($host, $port);

        if ($database !== 0) {
            $redis->select($database);
        }

        return new RedisCache($redis, $prefix);
    }

    /**
     * Create RedisCache from an already-connected Redis instance.
     */
    public static function redisFromInstance(Redis $redis, string $prefix = 'nextphp:'): RedisCache
    {
        return new RedisCache($redis, $prefix);
    }
}
