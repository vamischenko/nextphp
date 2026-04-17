<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use DateInterval;
use DateTimeImmutable;
use Nextphp\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

final class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $store = [];

    /** @var array<string, int|null> unix timestamp */
    private array $expiresAt = [];

    /** @var array<string, array<string, true>> */
    private array $tagMap = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        if (! $this->has($key)) {
            return $default;
        }

        return $this->store[$key];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertValidKey($key);

        $this->store[$key] = $value;
        $this->expiresAt[$key] = $this->resolveTtl($ttl);

        return true;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function delete(string $key): bool
    {
        $this->assertValidKey($key);

        unset($this->store[$key], $this->expiresAt[$key]);

        foreach ($this->tagMap as $tag => $keys) {
            unset($this->tagMap[$tag][$key]);
        }

        return true;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function clear(): bool
    {
        $this->store = [];
        $this->expiresAt = [];
        $this->tagMap = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey($key);

        if (! array_key_exists($key, $this->store)) {
            return false;
        }

        $expiresAt = $this->expiresAt[$key] ?? null;
        if ($expiresAt !== null && $expiresAt <= time()) {
            $this->delete($key);

            return false;
        }

        return true;
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
     * @param string[] $tags
       * @psalm-external-mutation-free
     */
    public function tag(string $key, array $tags): void
    {
        $this->assertValidKey($key);
        foreach ($tags as $tag) {
            $this->assertValidTag($tag);
            $this->tagMap[$tag][$key] = true;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function flushTag(string $tag): bool
    {
        $this->assertValidTag($tag);
        $keys = array_keys($this->tagMap[$tag] ?? []);
        foreach ($keys as $key) {
            $this->delete($key);
        }
        unset($this->tagMap[$tag]);

        return true;
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

    /**
     * @psalm-pure
     */
    private function assertValidTag(string $tag): void
    {
        if ($tag === '') {
            throw new InvalidArgumentException('Tag must not be empty.');
        }
    }

    private function resolveTtl(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            if ($ttl <= 0) {
                return time() - 1;
            }

            return time() + $ttl;
        }

        $now = new DateTimeImmutable();

        return $now->add($ttl)->getTimestamp();
    }
}
