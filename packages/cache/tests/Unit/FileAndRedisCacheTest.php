<?php

declare(strict_types=1);

namespace Nextphp\Cache\Tests\Unit;

use Nextphp\Cache\FileCache;
use Nextphp\Cache\RedisCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileCache::class)]
#[CoversClass(RedisCache::class)]
final class FileAndRedisCacheTest extends TestCase
{
    #[Test]
    public function fileCacheStoresAndReadsValues(): void
    {
        $dir = sys_get_temp_dir() . '/nextphp_file_cache_' . uniqid();
        $cache = new FileCache($dir);
        $cache->set('a', 'b');

        self::assertTrue($cache->has('a'));
        self::assertSame('b', $cache->get('a'));
    }

    #[Test]
    #[RequiresPhpExtension('redis')]
    public function redisCacheGetReturnsDefaultOnMiss(): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis->method('get')->willReturn(false);

        $cache = new RedisCache($redis);

        self::assertSame('default', $cache->get('missing', 'default'));
    }

    #[Test]
    #[RequiresPhpExtension('redis')]
    public function redisCacheHasReturnsTrueOnHit(): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis->method('exists')->willReturn(1);

        $cache = new RedisCache($redis);

        self::assertTrue($cache->has('key'));
    }

    #[Test]
    public function fileCacheRemember(): void
    {
        $dir   = sys_get_temp_dir() . '/nextphp_file_remember_' . uniqid();
        $cache = new FileCache($dir);
        $calls = 0;

        $v1 = $cache->remember('k', 60, function () use (&$calls) {
            $calls++;
            return 'hello';
        });
        $v2 = $cache->remember('k', 60, function () use (&$calls) {
            $calls++;
            return 'world';
        });

        self::assertSame('hello', $v1);
        self::assertSame('hello', $v2);
        self::assertSame(1, $calls);
    }

    #[Test]
    public function fileCacheTagsAndFlush(): void
    {
        $dir   = sys_get_temp_dir() . '/nextphp_file_tags_' . uniqid();
        $cache = new FileCache($dir);

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', 3);
        $cache->tag('a', ['group1']);
        $cache->tag('b', ['group1']);
        $cache->tag('c', ['group2']);

        $cache->flushTag('group1');

        self::assertFalse($cache->has('a'));
        self::assertFalse($cache->has('b'));
        self::assertTrue($cache->has('c'));
    }

    #[Test]
    #[RequiresPhpExtension('redis')]
    public function redisCacheRemember(): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis->method('get')->willReturn(false);
        $redis->method('set')->willReturn(true);
        $redis->method('exists')->willReturn(0);

        $cache = new RedisCache($redis);
        $calls = 0;

        $value = $cache->remember('key', 60, function () use (&$calls) {
            $calls++;
            return 'computed';
        });

        self::assertSame('computed', $value);
        self::assertSame(1, $calls);
    }

    #[Test]
    #[RequiresPhpExtension('redis')]
    public function redisCacheTagsAndFlush(): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis->method('sAdd')->willReturn(1);
        $redis->method('sMembers')->willReturn(['key1', 'key2']);
        $redis->method('del');

        $cache = new RedisCache($redis);

        $cache->tag('key1', ['my-tag']);
        $cache->tag('key2', ['my-tag']);
        $result = $cache->flushTag('my-tag');

        self::assertTrue($result);
    }
}
