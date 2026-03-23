<?php

declare(strict_types=1);

namespace Nextphp\Orm\Schema;

final class Blueprint
{
    /** @var array<int, array<string, mixed>> */
    private array $columns = [];

    /** @var string[] */
    private array $primaryKeys = [];

    /** @var array<int, array{name: string, columns: string[], unique: bool}> */
    private array $indexes = [];

    /** @var array<int, array{column: string, references: string, on: string, onDelete: string}> */
    private array $foreignKeys = [];

    public function __construct(
        private readonly string $table,
    ) {
    }

    public function id(string $column = 'id'): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'INTEGER', 'autoincrement' => true, 'nullable' => false, 'default' => null];
        $this->primaryKeys[] = $column;

        return $this;
    }

    public function bigIncrements(string $column): self
    {
        return $this->id($column);
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'VARCHAR(' . $length . ')', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'TEXT', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function integer(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'INTEGER', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function bigInteger(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'BIGINT', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function float(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'REAL', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function decimal(string $column, int $precision = 10, int $scale = 2): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'DECIMAL(' . $precision . ',' . $scale . ')', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function boolean(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'TINYINT(1)', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function timestamp(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'TIMESTAMP', 'autoincrement' => false, 'nullable' => true, 'default' => null];

        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');

        return $this;
    }

    public function softDeletes(string $column = 'deleted_at'): self
    {
        $this->timestamp($column);

        return $this;
    }

    public function json(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'TEXT', 'autoincrement' => false, 'nullable' => true, 'default' => null];

        return $this;
    }

    public function nullable(): self
    {
        $last = array_key_last($this->columns);

        if ($last !== null) {
            $this->columns[$last]['nullable'] = true;
        }

        return $this;
    }

    public function default(mixed $value): self
    {
        $last = array_key_last($this->columns);

        if ($last !== null) {
            $this->columns[$last]['default'] = $value;
        }

        return $this;
    }

    public function unique(): self
    {
        $last = array_key_last($this->columns);

        if ($last !== null) {
            $columnName = $this->columns[$last]['name'];
            $this->indexes[] = ['name' => $this->table . '_' . $columnName . '_unique', 'columns' => [$columnName], 'unique' => true];
        }

        return $this;
    }

    public function index(string $column): self
    {
        $this->indexes[] = ['name' => $this->table . '_' . $column . '_index', 'columns' => [$column], 'unique' => false];

        return $this;
    }

    public function foreign(string $column): self
    {
        // Returns self for fluent chaining via references/on/onDelete
        $this->foreignKeys[] = ['column' => $column, 'references' => 'id', 'on' => '', 'onDelete' => 'RESTRICT'];

        return $this;
    }

    public function references(string $column): self
    {
        $last = array_key_last($this->foreignKeys);

        if ($last !== null) {
            $this->foreignKeys[$last]['references'] = $column;
        }

        return $this;
    }

    public function on(string $table): self
    {
        $last = array_key_last($this->foreignKeys);

        if ($last !== null) {
            $this->foreignKeys[$last]['on'] = $table;
        }

        return $this;
    }

    public function onDelete(string $action): self
    {
        $last = array_key_last($this->foreignKeys);

        if ($last !== null) {
            $this->foreignKeys[$last]['onDelete'] = strtoupper($action);
        }

        return $this;
    }

    public function toCreateSql(): string
    {
        $columnDefs = [];

        foreach ($this->columns as $col) {
            $def = '"' . $col['name'] . '" ' . $col['type'];

            if ($col['autoincrement'] === true) {
                $def .= ' PRIMARY KEY AUTOINCREMENT';
            } elseif ($col['nullable'] === false) {
                $def .= ' NOT NULL';
            }

            if ($col['default'] !== null) {
                $def .= ' DEFAULT ' . (is_string($col['default']) ? "'" . $col['default'] . "'" : $col['default']);
            }

            if ($col['nullable'] === true && $col['autoincrement'] === false) {
                $def = str_replace(' NOT NULL', '', $def);
            }

            $columnDefs[] = $def;
        }

        if ($this->primaryKeys !== [] && ! str_contains(implode(',', $columnDefs), 'PRIMARY KEY')) {
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', array_map(fn ($k) => '"' . $k . '"', $this->primaryKeys)) . ')';
        }

        $sql = 'CREATE TABLE IF NOT EXISTS "' . $this->table . '" (' . implode(', ', $columnDefs) . ')';

        return $sql;
    }

    public function toDropSql(): string
    {
        return 'DROP TABLE IF EXISTS "' . $this->table . '"';
    }

    /**
     * @return string[]
     */
    public function toIndexSql(): array
    {
        $sqls = [];

        foreach ($this->indexes as $index) {
            $unique = $index['unique'] ? 'UNIQUE ' : '';
            $sqls[] = sprintf(
                'CREATE %sINDEX IF NOT EXISTS "%s" ON "%s" (%s)',
                $unique,
                $index['name'],
                $this->table,
                implode(', ', array_map(fn ($c) => '"' . $c . '"', $index['columns'])),
            );
        }

        return $sqls;
    }
}
