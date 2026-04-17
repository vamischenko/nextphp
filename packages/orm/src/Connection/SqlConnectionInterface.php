<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

use Nextphp\Orm\Connection\Driver\DriverInterface;
use Nextphp\Orm\Query\GrammarInterface;

/**
 * Extended interface for SQL-based connections (PDO).
 * NoSQL connections (MongoDB, ClickHouse) implement ConnectionInterface directly.
 */
/**
 * @psalm-mutable
 */
interface SqlConnectionInterface extends ConnectionInterface
{
    /**
     * Return the SQL grammar for this driver (LIMIT/OFFSET, quoting, etc.).
     */
    /**
     * @psalm-impure
     */
    public function getGrammar(): GrammarInterface;

    /**
     * Return the underlying PDO driver.
     */
    /**
     * @psalm-impure
     */
    public function getDriver(): DriverInterface;

    /**
     * Return the underlying PDO instance.
     */
    /**
     * @psalm-impure
     */
    public function getPdo(): \PDO;
}
