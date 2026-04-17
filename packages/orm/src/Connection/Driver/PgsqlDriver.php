<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection\Driver;

final class PgsqlDriver implements DriverInterface
{
    /**
      * @psalm-pure
     */
    public function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
      * @psalm-pure
     */
    public function getName(): string
    {
        return 'pgsql';
    }

    public function lastInsertId(\PDO $pdo, ?string $sequence = null): string|false
    {
        return $pdo->lastInsertId($sequence);
    }

    /**
      * @psalm-pure
     */
    public function compileLimitOffset(?int $limit, ?int $offset): string
    {
        $sql = '';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        if ($offset !== null) {
            $sql .= ' OFFSET ' . $offset;
        }

        return $sql;
    }
}
