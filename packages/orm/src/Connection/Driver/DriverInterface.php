<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection\Driver;

use Nextphp\Orm\Query\GrammarInterface;

/**
 * @psalm-mutable
 */
interface DriverInterface extends GrammarInterface
{
    /**
     * Return the last inserted ID after an INSERT.
     */
    /**
     * @psalm-impure
     */
    public function lastInsertId(\PDO $pdo, ?string $sequence = null): string|false;
}
