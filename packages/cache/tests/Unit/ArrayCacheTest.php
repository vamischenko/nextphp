<?php

declare(strict_types=1);

namespace Nextphp\Cache\Tests\Unit;

use Nextphp\Cache\ArrayCache;
use Nextphp\Cache\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayCache::class)]
final class ArrayCacheTest extends TestCase
{
    #[Test]
    public function setGetAndHas(): void
    {
        $cache = new ArrayCache();
        $cache->set('foo', 'bar');

        self::assertTrue($cache->has('foo'));
        self::assertSame('bar', $cache->get('foo'));
    }

    #[Test]
    public function ttlExpiresValue(): void
    {
        $cache = new ArrayCache();
        $cache->set('foo', 'bar', 1);
        sleep(2);

        self::assertFalse($cache->has('foo'));
        self::assertNull($cache->get('foo'));
    }

    #[Test]
    public function rememberCachesResolverResult(): void
    {
        $cache = new ArrayCache();
        $calls = 0;

        $first = $cache->remember('k', 60, function () use (&$calls): int {
            $calls++;

            return 123;
        });
        $second = $cache->remember('k', 60, function () use (&$calls): int {
            $calls++;

            return 456;
        });

        self::assertSame(123, $first);
        self::assertSame(123, $second);
        self::assertSame(1, $calls);
    }

    #[Test]
    public function tagAndFlushTag(): void
    {
        $cache = new ArrayCache();
        $cache->set('u1', ['id' => 1]);
        $cache->set('u2', ['id' => 2]);
        $cache->tag('u1', ['users']);
        $cache->tag('u2', ['users']);

        $cache->flushTag('users');

        self::assertFalse($cache->has('u1'));
        self::assertFalse($cache->has('u2'));
    }

    #[Test]
    public function invalidKeyThrowsException(): void
    {
        $cache = new ArrayCache();
        $this->expectException(InvalidArgumentException::class);

        $cache->set('invalid/key', 'v');
    }
}
