<?php

declare(strict_types=1);

namespace Nextphp\Cache;

use DateInterval;
use DateTimeImmutable;
use Nextphp\Cache\Exception\InvalidArgumentException;
use PDO;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 cache backed by a relational database (PDO).
 *
 * Required table schema (SQLite / MySQL / PostgreSQL compatible):
 *
 *   CREATE TABLE cache (
 *       key        VARCHAR(255) PRIMARY KEY,
 *       value      TEXT         NOT NULL,
 *       expires_at INTEGER      NULL
 *   );
 *
 *   CREATE TABLE cache_tags (
 *       tag        VARCHAR(255) NOT NULL,
 *       cache_key  VARCHAR(255) NOT NULL,
 *       PRIMARY KEY (tag, cache_key)
 *   );
 */
final class DatabaseCache implements CacheInterface
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'cache',
        private readonly string $tagsTable = 'cache_tags',
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        $stmt = $this->pdo->prepare(
            "SELECT value, expires_at FROM {$this->table} WHERE key = ?",
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return $default;
        }

        $expiresAt = $row['expires_at'] !== null ? (int) $row['expires_at'] : null;
        if ($expiresAt !== null && $expiresAt <= time()) {
            $this->delete($key);
            return $default;
        }

        return unserialize((string) $row['value']);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertValidKey($key);

        $expiresAt = $this->resolveExpiresAt($ttl);

        // Upsert: delete + insert (portable across drivers)
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE key = ?")->execute([$key]);
        $this->pdo->prepare(
            "INSERT INTO {$this->table} (key, value, expires_at) VALUES (?, ?, ?)",
        )->execute([$key, serialize($value), $expiresAt]);

        return true;
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE key = ?")->execute([$key]);
        $this->pdo->prepare("DELETE FROM {$this->tagsTable} WHERE cache_key = ?")->execute([$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->pdo->exec("DELETE FROM {$this->tagsTable}");
        $this->pdo->exec("DELETE FROM {$this->table}");

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /** @param iterable<mixed> $values */
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
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Cache key must be a string.');
            }
            $this->delete($key);
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
        $this->assertValidKey($key);
        foreach ($tags as $tag) {
            // Ignore duplicate — rely on PRIMARY KEY constraint
            try {
                $this->pdo->prepare(
                    "INSERT INTO {$this->tagsTable} (tag, cache_key) VALUES (?, ?)",
                )->execute([$tag, $key]);
            } catch (\PDOException) {
                // already exists
            }
        }
    }

    public function flushTag(string $tag): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT cache_key FROM {$this->tagsTable} WHERE tag = ?",
        );
        $stmt->execute([$tag]);
        $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        $this->pdo->prepare("DELETE FROM {$this->tagsTable} WHERE tag = ?")->execute([$tag]);

        return true;
    }

    /** Create the required tables if they don't exist (SQLite / MySQL / PostgreSQL). */
    public function createSchema(): void
    {
        $this->pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS {$this->table} (
            key        VARCHAR(255) NOT NULL PRIMARY KEY,
            value      TEXT         NOT NULL,
            expires_at INTEGER      NULL
        )
        SQL);

        $this->pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS {$this->tagsTable} (
            tag        VARCHAR(255) NOT NULL,
            cache_key  VARCHAR(255) NOT NULL,
            PRIMARY KEY (tag, cache_key)
        )
        SQL);
    }

    private function resolveExpiresAt(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl <= 0 ? time() - 1 : time() + $ttl;
        }

        return (new DateTimeImmutable())->add($ttl)->getTimestamp();
    }

    /** @psalm-mutation-free */
    private function assertValidKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must not be empty.');
        }

        if (preg_match('/[{}()\/\\\\@:]/', $key) === 1) {
            throw new InvalidArgumentException('Cache key contains reserved characters.');
        }
    }
}
