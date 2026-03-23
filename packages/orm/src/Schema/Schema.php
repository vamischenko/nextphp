<?php

declare(strict_types=1);

namespace Nextphp\Orm\Schema;

use Closure;
use Nextphp\Orm\Connection\ConnectionInterface;

final class Schema
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
    }

    public function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $this->connection->statement($blueprint->toCreateSql());

        foreach ($blueprint->toIndexSql() as $indexSql) {
            $this->connection->statement($indexSql);
        }
    }

    public function drop(string $table): void
    {
        $this->connection->statement('DROP TABLE IF EXISTS "' . $table . '"');
    }

    public function dropIfExists(string $table): void
    {
        $this->drop($table);
    }

    public function hasTable(string $table): bool
    {
        $sql = match ($this->connection->getDriverName()) {
            'sqlite'      => "SELECT name FROM sqlite_master WHERE type='table' AND name=?",
            'mysql'       => 'SHOW TABLES LIKE ?',
            'pgsql'       => "SELECT tablename FROM pg_tables WHERE schemaname='public' AND tablename=?",
            'clickhouse'  => 'SELECT name FROM system.tables WHERE database = currentDatabase() AND name = ?',
            default       => "SELECT name FROM sqlite_master WHERE type='table' AND name=?",
        };

        return $this->connection->selectOne($sql, [$table]) !== null;
    }

    public function rename(string $from, string $to): void
    {
        $this->connection->statement(sprintf('ALTER TABLE "%s" RENAME TO "%s"', $from, $to));
    }
}
