<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use Memcached;
use PDO;
use Redis;

final class CacheFactory
{
    /**
     * @psalm-pure
     */
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
     *
     * @psalm-pure
     */
    public static function redisFromInstance(Redis $redis, string $prefix = 'nextphp:'): RedisCache
    {
        return new RedisCache($redis, $prefix);
    }

    /**
     * @psalm-pure
     */
    public static function memcached(
        string $host = '127.0.0.1',
        int $port = 11211,
        string $prefix = 'nextphp:',
    ): MemcachedCache {
        $mc = new Memcached();
        $mc->addServer($host, $port);

        return new MemcachedCache($mc, $prefix);
    }

    /**
     * Create MemcachedCache from an already-configured Memcached instance.
     */
    public static function memcachedFromInstance(Memcached $mc, string $prefix = 'nextphp:'): MemcachedCache
    {
        return new MemcachedCache($mc, $prefix);
    }

    /**
     * @psalm-pure
     */
    public static function database(
        PDO $pdo,
        string $table = 'cache',
        string $tagsTable = 'cache_tags',
    ): DatabaseCache {
        return new DatabaseCache($pdo, $table, $tagsTable);
    }
}
