<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

/**
 * @psalm-mutable
 */
interface ConnectionInterface
{
    /**
     * Execute a SELECT query and return all rows.
     *
     * @param mixed[] $bindings
     * @return array<int, array<string, mixed>>
     * @psalm-impure
     */
    public function select(string $sql, array $bindings = []): array;

    /**
     * Execute a SELECT query and return the first row.
     *
     * @param mixed[] $bindings
     * @return array<string, mixed>|null
     * @psalm-impure
     */
    public function selectOne(string $sql, array $bindings = []): ?array;

    /**
     * Execute an INSERT and return the last insert ID (or document ID for NoSQL).
     *
     * @param mixed[] $bindings
     * @psalm-impure
     */
    public function insert(string $sql, array $bindings = []): string|false;

    /**
     * Execute an UPDATE/DELETE statement and return the number of affected rows.
     *
     * @param mixed[] $bindings
     * @psalm-impure
     */
    public function affectingStatement(string $sql, array $bindings = []): int;

    /**
     * Execute a raw DDL or administrative statement.
     *
     * @param mixed[] $bindings
     * @psalm-impure
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function statement(string $sql, array $bindings = []): bool;

    /**
     * Begin a transaction (or savepoint for nested transactions).
     * @psalm-impure
     */
    public function beginTransaction(): void;

    /**
     * Commit the current transaction.
     * @psalm-impure
     */
    public function commit(): void;

    /**
     * Roll back the current transaction.
     * @psalm-impure
     */
    public function rollBack(): void;

    /**
     * Execute a callable within a transaction, rolling back on exception.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     * @psalm-impure
     */
    public function transaction(callable $callback): mixed;

    /**
     * Return the driver name (e.g. "mysql", "sqlite", "clickhouse", "mongodb").
     * @psalm-impure
     */
    public function getDriverName(): string;

    /**
     * Enable query logging.
     * @psalm-impure
     */
    public function enableQueryLog(): void;

    /**
     * Return the query log entries.
     *
     * @return array<string, mixed>[]
     * @psalm-impure
     */
    public function getQueryLog(): array;

    /**
     * Flush the query log.
     * @psalm-impure
     */
    public function flushQueryLog(): void;
}
