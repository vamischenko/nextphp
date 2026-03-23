<?php

declare(strict_types=1);

namespace Nextphp\Orm\Migration;

use Nextphp\Orm\Connection\ConnectionInterface;
use Nextphp\Orm\Schema\Schema;

abstract class Migration
{
    protected Schema $schema;

    protected ConnectionInterface $connection;

    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
    }

    abstract public function up(): void;

    abstract public function down(): void;
}
