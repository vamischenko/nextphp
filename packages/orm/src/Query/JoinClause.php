<?php

declare(strict_types=1);

namespace Nextphp\Orm\Query;

/**
 * @psalm-immutable
 */
final class JoinClause
{
    /** @var array<int, array{column: string, operator: string, value: string, boolean: string}> */
    private array $conditions = [];

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $type,
        public readonly string $table,
    ) {
    }

    /**
      * @psalm-external-mutation-free
     */
    public function on(string $first, string $operator, string $second, string $boolean = 'AND'): self
    {
        $this->conditions[] = [
            'column'   => $first,
            'operator' => $operator,
            'value'    => $second,
            'boolean'  => $boolean,
        ];

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function orOn(string $first, string $operator, string $second): self
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    /**
      * @psalm-mutation-free
     */
    public function toSql(): string
    {
        if ($this->conditions === []) {
            return '';
        }

        $parts = [];

        foreach ($this->conditions as $i => $condition) {
            $prefix = $i === 0 ? '' : $condition['boolean'] . ' ';
            $parts[] = $prefix . $condition['column'] . ' ' . $condition['operator'] . ' ' . $condition['value'];
        }

        return $this->type . ' JOIN ' . $this->table . ' ON ' . implode(' ', $parts);
    }
}
