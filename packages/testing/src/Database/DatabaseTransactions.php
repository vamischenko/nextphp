<?php

declare(strict_types=1);

namespace Nextphp\Testing\Database;

use Nextphp\Orm\Connection\Connection;

trait DatabaseTransactions
{
    protected function beginTransaction(Connection $connection): void
    {
        $connection->beginTransaction();
    }

    protected function rollbackTransaction(Connection $connection): void
    {
        $connection->rollBack();
    }
}
