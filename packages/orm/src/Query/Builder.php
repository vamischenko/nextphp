<?php

declare(strict_types=1);

namespace Nextphp\Orm\Query;

use Closure;
use Nextphp\Orm\Connection\ConnectionInterface;
use Nextphp\Orm\Connection\SqlConnectionInterface;

class Builder
{
    private ?string $table = null;

    /** @var string[] */
    private array $columns = ['*'];

    private bool $distinct = false;

    /** @var array<int, array{sql: string, bindings: mixed[], boolean: string}> */
    private array $wheres = [];

    /** @var JoinClause[] */
    private array $joins = [];

    /** @var string[] */
    private array $orderBys = [];

    /** @var string[] */
    private array $groupBys = [];

    /** @var array<int, array{sql: string, bindings: mixed[], boolean: string}> */
    private array $havings = [];

    private ?int $limit = null;

    private ?int $offset = null;

    /** @var mixed[] accumulated bindings in order */
    private array $bindings = [];

    private bool $withoutSoftDeleteScope = false;

    private bool $onlyTrashed = false;

    /** @var string[] */
    private array $eagerLoads = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
    }

    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param string[]|string $relations
     */
    public function with(array|string $relations): static
    {
        $rels = is_array($relations) ? $relations : [$relations];
        $this->eagerLoads = array_values(array_unique(array_merge($this->eagerLoads, $rels)));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getEagerLoads(): array
    {
        return $this->eagerLoads;
    }

    public function withTrashed(): static
    {
        $this->withoutSoftDeleteScope = true;

        return $this;
    }

    public function onlyTrashed(string $deletedAtColumn = 'deleted_at'): static
    {
        $this->withoutSoftDeleteScope = true;
        $this->onlyTrashed = true;
        $this->whereNotNull($deletedAtColumn);

        return $this;
    }

    public function shouldApplySoftDeleteScope(): bool
    {
        return ! $this->withoutSoftDeleteScope;
    }

    /**
     * @param string|string[] $columns
     */
    public function select(string|array $columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : [$columns];

        return $this;
    }

    public function addSelect(string $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    // --- WHERE ---

    /**
     * @param mixed $value
     */
    public function where(string|Closure $column, string $operator = '=', mixed $value = null, string $boolean = 'AND'): static
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        if ($value instanceof Expression) {
            $this->wheres[] = ['sql' => $column . ' ' . $operator . ' ' . $value->value, 'bindings' => [], 'boolean' => $boolean];
        } else {
            $this->wheres[] = ['sql' => $column . ' ' . $operator . ' ?', 'bindings' => [$value], 'boolean' => $boolean];
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function orWhere(string $column, string $operator = '=', mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * @param mixed[] $values
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        if ($values === []) {
            // Empty IN = always false
            $this->wheres[] = ['sql' => '0 = 1', 'bindings' => [], 'boolean' => $boolean];

            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $operator = $not ? 'NOT IN' : 'IN';
        $this->wheres[] = ['sql' => $column . ' ' . $operator . ' (' . $placeholders . ')', 'bindings' => $values, 'boolean' => $boolean];

        return $this;
    }

    /**
     * @param mixed[] $values
     */
    public function whereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'AND', true);
    }

    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $this->wheres[] = ['sql' => $column . ($not ? ' IS NOT NULL' : ' IS NULL'), 'bindings' => [], 'boolean' => $boolean];

        return $this;
    }

    public function whereNotNull(string $column): static
    {
        return $this->whereNull($column, 'AND', true);
    }

    /**
     * @param mixed $min
     * @param mixed $max
     */
    public function whereBetween(string $column, mixed $min, mixed $max, string $boolean = 'AND', bool $not = false): static
    {
        $operator = $not ? 'NOT BETWEEN' : 'BETWEEN';
        $this->wheres[] = ['sql' => $column . ' ' . $operator . ' ? AND ?', 'bindings' => [$min, $max], 'boolean' => $boolean];

        return $this;
    }

    public function whereRaw(string $sql, mixed ...$bindings): static
    {
        $this->wheres[] = ['sql' => $sql, 'bindings' => $bindings, 'boolean' => 'AND'];

        return $this;
    }

    private function whereNested(Closure $callback, string $boolean): static
    {
        $nested = new static($this->connection);
        $callback($nested);

        [$nestedSql, $nestedBindings] = $nested->compileWheres();

        if ($nestedSql !== '') {
            $this->wheres[] = ['sql' => '(' . $nestedSql . ')', 'bindings' => $nestedBindings, 'boolean' => $boolean];
        }

        return $this;
    }

    // --- JOINS ---

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $join = new JoinClause(strtoupper($type), $table);
        $join->on($first, $operator, $second);
        $this->joins[] = $join;

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function joinUsing(string $table, \Closure $callback, string $type = 'INNER'): static
    {
        $join = new JoinClause(strtoupper($type), $table);
        $callback($join);
        $this->joins[] = $join;

        return $this;
    }

    // --- ORDER / GROUP / LIMIT ---

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBys[] = $column . ' ' . strtoupper($direction);

        return $this;
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function groupBy(string ...$columns): static
    {
        foreach ($columns as $col) {
            $this->groupBys[] = $col;
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        $this->havings[] = ['sql' => $column . ' ' . $operator . ' ?', 'bindings' => [$value], 'boolean' => $boolean];

        return $this;
    }

    public function havingRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->havings[] = ['sql' => $sql, 'bindings' => $bindings, 'boolean' => $boolean];

        return $this;
    }

    public function limit(int $value): static
    {
        $this->limit = $value;

        return $this;
    }

    public function offset(int $value): static
    {
        $this->offset = $value;

        return $this;
    }

    public function take(int $value): static
    {
        return $this->limit($value);
    }

    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    // --- EXECUTION ---

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->limit(1)->connection->selectOne($this->toSql(), $this->getBindings());
    }

    public function count(string $column = '*'): int
    {
        $clone = clone $this;
        $clone->columns = ['COUNT(' . $column . ') as aggregate'];
        $clone->limit = null;
        $clone->offset = null;
        $result = $clone->connection->selectOne($clone->toSql(), $clone->getBindings());

        return (int) ($result['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function doesntExist(): bool
    {
        return ! $this->exists();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values): string|false
    {
        $columns = array_keys($values);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            (string) $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        return $this->connection->insert($sql, array_values($values));
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): int
    {
        $sets = array_map(fn ($col) => $col . ' = ?', array_keys($values));

        [$whereSql, $whereBindings] = $this->compileWheres();

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            (string) $this->table,
            implode(', ', $sets),
            $whereSql !== '' ? ' WHERE ' . $whereSql : '',
        );

        $bindings = array_merge(array_values($values), $whereBindings);

        return $this->connection->affectingStatement($sql, $bindings);
    }

    public function delete(): int
    {
        [$whereSql, $whereBindings] = $this->compileWheres();

        $sql = sprintf(
            'DELETE FROM %s%s',
            (string) $this->table,
            $whereSql !== '' ? ' WHERE ' . $whereSql : '',
        );

        return $this->connection->affectingStatement($sql, $whereBindings);
    }

    public function toSql(): string
    {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->columns);
        $sql .= ' FROM ' . (string) $this->table;

        foreach ($this->joins as $join) {
            $sql .= ' ' . $join->toSql();
        }

        [$whereSql] = $this->compileWheres();

        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        if ($this->groupBys !== []) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBys);
        }

        [$havingSql] = $this->compileHavings();

        if ($havingSql !== '') {
            $sql .= ' HAVING ' . $havingSql;
        }

        if ($this->orderBys !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        if ($this->connection instanceof SqlConnectionInterface) {
            $sql .= $this->connection->getGrammar()->compileLimitOffset($this->limit, $this->offset);
        }

        return $sql;
    }

    /**
     * @return mixed[]
     */
    public function getBindings(): array
    {
        $bindings = [];

        foreach ($this->wheres as $where) {
            $bindings = array_merge($bindings, $where['bindings']);
        }

        foreach ($this->havings as $having) {
            $bindings = array_merge($bindings, $having['bindings']);
        }

        return $bindings;
    }

    public static function raw(string $value): Expression
    {
        return new Expression($value);
    }

    /**
     * @return array{0: string, 1: mixed[]}
     */
    private function compileWheres(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }

        $parts = [];
        $bindings = [];

        foreach ($this->wheres as $i => $where) {
            $prefix = $i === 0 ? '' : $where['boolean'] . ' ';
            $parts[] = $prefix . $where['sql'];
            $bindings = array_merge($bindings, $where['bindings']);
        }

        return [implode(' ', $parts), $bindings];
    }

    /**
     * @return array{0: string, 1: mixed[]}
     */
    private function compileHavings(): array
    {
        if ($this->havings === []) {
            return ['', []];
        }

        $parts = [];
        $bindings = [];

        foreach ($this->havings as $i => $having) {
            $prefix = $i === 0 ? '' : $having['boolean'] . ' ';
            $parts[] = $prefix . $having['sql'];
            $bindings = array_merge($bindings, $having['bindings']);
        }

        return [implode(' ', $parts), $bindings];
    }
}
