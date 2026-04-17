<?php

declare(strict_types=1);

namespace Nextphp\Migrations\Schema;

use Closure;
use Nextphp\Orm\Connection\ConnectionInterface;

final class Schema
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
    }

    public function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $this->connection->statement($blueprint->toCreateSql());
        foreach ($blueprint->toIndexSql() as $sql) {
            $this->connection->statement($sql);
        }
    }

    public function hasTable(string $table): bool
    {
        $row = $this->connection->selectOne("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);

        return $row !== null;
    }

    public function drop(string $table): void
    {
        $this->connection->statement('DROP TABLE IF EXISTS "' . $table . '"');
    }
}
