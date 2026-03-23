<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class RedisCache implements CacheInterface
{
    /**
     * Fallback storage for environments without redis extension.
     * @var array<string, mixed>
     */
    private array $fallback = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->fallback[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->fallback[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->fallback[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->fallback = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get((string) $key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->fallback);
    }
}
