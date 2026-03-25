<?php

declare(strict_types=1);

namespace Nextphp\Cache\Tests\Unit;

use Nextphp\Cache\DatabaseCache;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseCache::class)]
final class DatabaseCacheTest extends TestCase
{
    private DatabaseCache $cache;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->cache = new DatabaseCache($pdo);
        $this->cache->createSchema();
    }

    #[Test]
    public function setAndGet(): void
    {
        $this->cache->set('foo', 'bar');

        self::assertSame('bar', $this->cache->get('foo'));
    }

    #[Test]
    public function getMissingReturnsDefault(): void
    {
        self::assertSame('default', $this->cache->get('missing', 'default'));
    }

    #[Test]
    public function deletedKeyReturnsDefault(): void
    {
        $this->cache->set('key', 'val');
        $this->cache->delete('key');

        self::assertNull($this->cache->get('key'));
    }

    #[Test]
    public function expiredKeyReturnsDefault(): void
    {
        $this->cache->set('exp', 'value', -1);

        self::assertNull($this->cache->get('exp'));
    }

    #[Test]
    public function has(): void
    {
        $this->cache->set('exists', 1);

        self::assertTrue($this->cache->has('exists'));
        self::assertFalse($this->cache->has('missing'));
    }

    #[Test]
    public function clear(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->cache->clear();

        self::assertFalse($this->cache->has('a'));
        self::assertFalse($this->cache->has('b'));
    }

    #[Test]
    public function getMultiple(): void
    {
        $this->cache->set('x', 10);
        $this->cache->set('y', 20);

        $result = $this->cache->getMultiple(['x', 'y', 'z'], 0);

        self::assertSame(['x' => 10, 'y' => 20, 'z' => 0], $result);
    }

    #[Test]
    public function setMultiple(): void
    {
        $this->cache->setMultiple(['p' => 'P', 'q' => 'Q']);

        self::assertSame('P', $this->cache->get('p'));
        self::assertSame('Q', $this->cache->get('q'));
    }

    #[Test]
    public function deleteMultiple(): void
    {
        $this->cache->setMultiple(['r' => 1, 's' => 2]);
        $this->cache->deleteMultiple(['r', 's']);

        self::assertFalse($this->cache->has('r'));
        self::assertFalse($this->cache->has('s'));
    }

    #[Test]
    public function remember(): void
    {
        $calls = 0;
        $value = $this->cache->remember('memo', 60, function () use (&$calls) {
            $calls++;
            return 'computed';
        });

        self::assertSame('computed', $value);
        self::assertSame(1, $calls);

        // Second call should not invoke resolver
        $value2 = $this->cache->remember('memo', 60, function () use (&$calls) {
            $calls++;
            return 'recomputed';
        });

        self::assertSame('computed', $value2);
        self::assertSame(1, $calls);
    }

    #[Test]
    public function tagsAndFlushTag(): void
    {
        $this->cache->set('post.1', 'Post One');
        $this->cache->set('post.2', 'Post Two');
        $this->cache->set('user.1', 'User One');

        $this->cache->tag('post.1', ['posts']);
        $this->cache->tag('post.2', ['posts']);
        $this->cache->tag('user.1', ['users']);

        $this->cache->flushTag('posts');

        self::assertFalse($this->cache->has('post.1'));
        self::assertFalse($this->cache->has('post.2'));
        self::assertTrue($this->cache->has('user.1'));
    }

    #[Test]
    public function upsertOverwritesExistingKey(): void
    {
        $this->cache->set('key', 'first');
        $this->cache->set('key', 'second');

        self::assertSame('second', $this->cache->get('key'));
    }
}
