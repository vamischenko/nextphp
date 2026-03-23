<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use DateInterval;
use DateTimeImmutable;
use Nextphp\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Redis;

final class RedisCache implements CacheInterface
{
    public function __construct(
        private readonly Redis $redis,
        private readonly string $prefix = 'nextphp:',
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        $raw = $this->redis->get($this->prefixed($key));

        if ($raw === false) {
            return $default;
        }

        return unserialize((string) $raw);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertValidKey($key);

        $serialized = serialize($value);
        $seconds = $this->resolveTtlSeconds($ttl);

        if ($seconds === null) {
            return (bool) $this->redis->set($this->prefixed($key), $serialized);
        }

        if ($seconds <= 0) {
            $this->delete($key);

            return true;
        }

        return (bool) $this->redis->setex($this->prefixed($key), $seconds, $serialized);
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);
        $this->redis->del($this->prefixed($key));

        return true;
    }

    public function clear(): bool
    {
        $pattern = $this->prefix . '*';
        $keys = $this->redis->keys($pattern);

        if ($keys !== false && $keys !== []) {
            $this->redis->del(...$keys);
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keyList = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $this->assertValidKey($key);
            $keyList[] = $key;
        }

        if ($keyList === []) {
            return [];
        }

        $prefixed = array_map(fn (string $k) => $this->prefixed($k), $keyList);
        $values = $this->redis->mget($prefixed);

        $result = [];
        foreach ($keyList as $i => $key) {
            $raw = $values[$i] ?? false;
            $result[$key] = ($raw === false) ? $default : unserialize((string) $raw);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $prefixed = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $this->assertValidKey($key);
            $prefixed[] = $this->prefixed($key);
        }

        if ($prefixed !== []) {
            $this->redis->del(...$prefixed);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey($key);

        return (bool) $this->redis->exists($this->prefixed($key));
    }

    private function prefixed(string $key): string
    {
        return $this->prefix . $key;
    }

    private function assertValidKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must not be empty.');
        }

        if (preg_match('/[{}()\/\\\\@:]/', $key) === 1) {
            throw new InvalidArgumentException('Cache key contains reserved characters.');
        }
    }

    private function resolveTtlSeconds(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        $now = new DateTimeImmutable();

        return (int) ($now->add($ttl)->getTimestamp() - $now->getTimestamp());
    }
}
