<?php

declare(strict_types=1);

namespace Nextphp\Cache\Tests\Unit;

use Nextphp\Cache\FileCache;
use Nextphp\Cache\RedisCache;
use PHPUnit\Framework\Attributes\CoversClass;
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
    public function redisCacheFallbackWorks(): void
    {
        $cache = new RedisCache();
        $cache->set('x', 1);

        self::assertTrue($cache->has('x'));
        self::assertSame(1, $cache->get('x'));
    }
}
