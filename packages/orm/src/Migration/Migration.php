<?php

declare(strict_types=1);

namespace Nextphp\Orm\Migration;

use Nextphp\Orm\Connection\ConnectionInterface;
use Nextphp\Orm\Schema\Schema;

/**
 * @psalm-mutable
 */
abstract class Migration
{
    protected Schema $schema;

    protected ConnectionInterface $connection;

    /**
      * @psalm-external-mutation-free
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
    }

    /**
     * @psalm-impure
     */
    abstract public function up(): void;

    /**
     * @psalm-impure
     */
    abstract public function down(): void;
}
