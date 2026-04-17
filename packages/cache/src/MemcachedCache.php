<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use DateInterval;
use DateTimeImmutable;
use Memcached;
use Nextphp\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

final class MemcachedCache implements CacheInterface
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Memcached $memcached,
        private readonly string $prefix = 'nextphp:',
    ) {
    }

    /**
      * @psalm-mutation-free
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        $value = $this->memcached->get($this->prefixed($key));

        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertValidKey($key);

        $seconds = $this->resolveTtlSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            $this->delete($key);
            return true;
        }

        return $this->memcached->set(
            $this->prefixed($key),
            $value,
            $seconds ?? 0,
        );
    }

    /**
      * @psalm-mutation-free
     */
    public function delete(string $key): bool
    {
        $this->assertValidKey($key);
        $this->memcached->delete($this->prefixed($key));

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function clear(): bool
    {
        return $this->memcached->flush();
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

        /** @var array<string, mixed>|false $values */
        $values = $this->memcached->getMulti($prefixed);
        if ($values === false) {
            $values = [];
        }

        $result = [];
        foreach ($keyList as $origKey) {
            $prefixedKey       = $this->prefixed($origKey);
            $result[$origKey]  = array_key_exists($prefixedKey, $values) ? $values[$prefixedKey] : $default;
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
            $this->memcached->deleteMulti($prefixed);
        }

        return true;
    }

    /**
      * @psalm-mutation-free
     */
    public function has(string $key): bool
    {
        $this->assertValidKey($key);
        $this->memcached->get($this->prefixed($key));

        return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /**
     * @param callable(): mixed $resolver
     */
    public function remember(string $key, null|int|DateInterval $ttl, callable $resolver): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $resolver();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Tags are stored as serialized arrays under a special key.
     *
     * @param string[] $tags
       * @psalm-mutation-free
     */
    public function tag(string $key, array $tags): void
    {
        $this->assertValidKey($key);
        foreach ($tags as $tag) {
            $tagKey = $this->prefix . 'tag:' . $tag;
            /** @var string[]|false $current */
            $current = $this->memcached->get($tagKey);
            if (!is_array($current)) {
                $current = [];
            }
            if (!in_array($key, $current, true)) {
                $current[] = $key;
            }
            $this->memcached->set($tagKey, $current, 0);
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function flushTag(string $tag): bool
    {
        $tagKey = $this->prefix . 'tag:' . $tag;
        /** @var string[]|false $keys */
        $keys = $this->memcached->get($tagKey);
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $this->delete($key);
            }
        }
        $this->memcached->delete($tagKey);

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    private function prefixed(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * @psalm-pure
     */
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
