<?php

declare(strict_types=1);

namespace Nextphp\Testing\Database;

use Nextphp\Orm\Connection\Connection;

/** @psalm-api */
trait RefreshDatabase
{
    /** @param string[] $tables */
    protected function refreshSqliteSchema(Connection $connection, array $tables): void
    {
        foreach ($tables as $table) {
            $connection->statement('DROP TABLE IF EXISTS ' . $table);
        }
    }
}
