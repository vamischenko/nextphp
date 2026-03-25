<?php

declare(strict_types=1);

namespace Nextphp\Cache\Tests\Unit;

use Memcached;
use Nextphp\Cache\MemcachedCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemcachedCache::class)]
#[RequiresPhpExtension('memcached')]
final class MemcachedCacheTest extends TestCase
{
    private function makeCache(): MemcachedCache
    {
        $mc = $this->createMock(Memcached::class);

        // Default: key not found
        $mc->method('getResultCode')->willReturn(Memcached::RES_NOTFOUND);

        return new MemcachedCache($mc);
    }

    #[Test]
    public function getReturnsDefaultOnMiss(): void
    {
        $cache = $this->makeCache();

        self::assertSame('default', $cache->get('missing', 'default'));
    }

    #[Test]
    public function hasReturnsFalseOnMiss(): void
    {
        $cache = $this->makeCache();

        self::assertFalse($cache->has('missing'));
    }

    #[Test]
    public function hasReturnsTrueOnHit(): void
    {
        $mc = $this->createMock(Memcached::class);
        $mc->method('getResultCode')->willReturn(Memcached::RES_SUCCESS);

        $cache = new MemcachedCache($mc);

        self::assertTrue($cache->has('key'));
    }

    #[Test]
    public function setCallsMemcachedSet(): void
    {
        $mc = $this->createMock(Memcached::class);
        $mc->expects(self::once())
            ->method('set')
            ->with('nextphp:mykey', 'myvalue', 0)
            ->willReturn(true);

        $cache = new MemcachedCache($mc);
        self::assertTrue($cache->set('mykey', 'myvalue'));
    }

    #[Test]
    public function setWithTtlPassesSeconds(): void
    {
        $mc = $this->createMock(Memcached::class);
        $mc->expects(self::once())
            ->method('set')
            ->with('nextphp:k', 'v', 60)
            ->willReturn(true);

        $cache = new MemcachedCache($mc);
        $cache->set('k', 'v', 60);
    }

    #[Test]
    public function deleteCallsMemcachedDelete(): void
    {
        $mc = $this->createMock(Memcached::class);
        $mc->expects(self::once())->method('delete')->with('nextphp:k');

        $cache = new MemcachedCache($mc);
        self::assertTrue($cache->delete('k'));
    }

    #[Test]
    public function clearCallsFlush(): void
    {
        $mc = $this->createMock(Memcached::class);
        $mc->expects(self::once())->method('flush')->willReturn(true);

        $cache = new MemcachedCache($mc);
        self::assertTrue($cache->clear());
    }
}
