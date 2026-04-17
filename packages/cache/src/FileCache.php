<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use DateInterval;
use Nextphp\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

final class FileCache implements CacheInterface
{
    public function __construct(
        private readonly string $directory,
    ) {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0777, true) && ! is_dir($this->directory)) {
            throw new \RuntimeException('Failed to create cache directory.');
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);
        if (! is_file($path)) {
            return $default;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            return $default;
        }

        $expiresAt = $payload['expires_at'] ?? null;
        if (is_int($expiresAt) && $expiresAt <= time()) {
            @unlink($path);

            return $default;
        }

        return unserialize((string) ($payload['value'] ?? serialize(null)));
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $expiresAt = null;
        if (is_int($ttl)) {
            $expiresAt = time() + $ttl;
        } elseif ($ttl instanceof DateInterval) {
            $expiresAt = (new \DateTimeImmutable())->add($ttl)->getTimestamp();
        }

        $payload = ['value' => serialize($value), 'expires_at' => $expiresAt];
        file_put_contents($this->path($key), json_encode($payload, JSON_THROW_ON_ERROR));

        return true;
    }

    public function delete(string $key): bool
    {
        @unlink($this->path($key));

        return true;
    }

    public function clear(): bool
    {
        $files = scandir($this->directory) ?: [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                @unlink($this->directory . '/' . $file);
            }
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Cache key must be string.');
            }
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Cache key must be string.');
            }
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (is_string($key)) {
                $this->delete($key);
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
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
     */
    public function tag(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagPath = $this->tagPath($tag);
            $keys = $this->readTagFile($tagPath);
            $keys[$key] = true;
            file_put_contents($tagPath, json_encode($keys, JSON_THROW_ON_ERROR));
        }
    }

    public function flushTag(string $tag): bool
    {
        $tagPath = $this->tagPath($tag);
        $keys = $this->readTagFile($tagPath);
        foreach (array_keys($keys) as $key) {
            $this->delete($key);
        }
        @unlink($tagPath);

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    private function path(string $key): string
    {
        return rtrim($this->directory, '/') . '/' . sha1($key) . '.json';
    }

    /**
     * @psalm-mutation-free
     */
    private function tagPath(string $tag): string
    {
        return rtrim($this->directory, '/') . '/tag_' . sha1($tag) . '.json';
    }

    /**
     * @return array<string, true>
     */
    private function readTagFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }
}
