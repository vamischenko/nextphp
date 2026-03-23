<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

interface ConnectionInterface
{
    /**
     * Execute a SELECT query and return all rows.
     *
     * @param mixed[] $bindings
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array;

    /**
     * Execute a SELECT query and return the first row.
     *
     * @param mixed[] $bindings
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array;

    /**
     * Execute an INSERT and return the last insert ID (or document ID for NoSQL).
     *
     * @param mixed[] $bindings
     */
    public function insert(string $sql, array $bindings = []): string|false;

    /**
     * Execute an UPDATE/DELETE statement and return the number of affected rows.
     *
     * @param mixed[] $bindings
     */
    public function affectingStatement(string $sql, array $bindings = []): int;

    /**
     * Execute a raw DDL or administrative statement.
     *
     * @param mixed[] $bindings
     */
    public function statement(string $sql, array $bindings = []): bool;

    /**
     * Begin a transaction (or savepoint for nested transactions).
     */
    public function beginTransaction(): void;

    /**
     * Commit the current transaction.
     */
    public function commit(): void;

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): void;

    /**
     * Execute a callable within a transaction, rolling back on exception.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    /**
     * Return the driver name (e.g. "mysql", "sqlite", "clickhouse", "mongodb").
     */
    public function getDriverName(): string;

    /**
     * Enable query logging.
     */
    public function enableQueryLog(): void;

    /**
     * Return the query log entries.
     *
     * @return array<string, mixed>[]
     */
    public function getQueryLog(): array;

    /**
     * Flush the query log.
     */
    public function flushQueryLog(): void;
}
