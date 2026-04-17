<?php

declare(strict_types=1);

namespace Nextphp\Orm\Exception;

use Throwable;

final class QueryException extends OrmException
{
    /**
     * @param mixed[] $bindings
       * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $sql,
        private readonly array $bindings,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Query failed: %s — Bindings: %s', $sql, json_encode($bindings)),
            0,
            $previous,
        );
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return mixed[]
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
