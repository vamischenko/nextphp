<?php

declare(strict_types=1);

namespace Nextphp\Orm\Query;

interface GrammarInterface
{
    /**
     * Wrap a table or column name in driver-specific quotes.
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Return the driver name (e.g. "mysql", "sqlite", "clickhouse").
     */
    public function getName(): string;

    /**
     * Compile a LIMIT/OFFSET clause in driver-specific SQL syntax.
     */
    public function compileLimitOffset(?int $limit, ?int $offset): string;
}
