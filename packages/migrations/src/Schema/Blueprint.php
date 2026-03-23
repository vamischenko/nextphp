<?php

declare(strict_types=1);

namespace Nextphp\Migrations\Schema;

final class Blueprint
{
    /** @var array<int, array<string, mixed>> */
    private array $columns = [];

    /** @var array<int, array{name: string, columns: string[], unique: bool}> */
    private array $indexes = [];

    public function __construct(private readonly string $table)
    {
    }

    public function id(string $column = 'id'): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'INTEGER', 'autoincrement' => true, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'VARCHAR(' . $length . ')', 'autoincrement' => false, 'nullable' => false, 'default' => null];

        return $this;
    }

    public function timestamp(string $column): self
    {
        $this->columns[] = ['name' => $column, 'type' => 'TIMESTAMP', 'autoincrement' => false, 'nullable' => true, 'default' => null];

        return $this;
    }

    public function timestamps(): self
    {
        return $this->timestamp('created_at')->timestamp('updated_at');
    }

    public function softDeletes(string $column = 'deleted_at'): self
    {
        return $this->timestamp($column);
    }

    public function nullable(): self
    {
        $idx = array_key_last($this->columns);
        if ($idx !== null) {
            $this->columns[$idx]['nullable'] = true;
        }

        return $this;
    }

    public function default(mixed $value): self
    {
        $idx = array_key_last($this->columns);
        if ($idx !== null) {
            $this->columns[$idx]['default'] = $value;
        }

        return $this;
    }

    public function unique(): self
    {
        $idx = array_key_last($this->columns);
        if ($idx !== null) {
            $name = $this->columns[$idx]['name'];
            $this->indexes[] = ['name' => $this->table . '_' . $name . '_unique', 'columns' => [$name], 'unique' => true];
        }

        return $this;
    }

    public function toCreateSql(): string
    {
        $defs = [];
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
            $defs[] = $def;
        }

        return 'CREATE TABLE IF NOT EXISTS "' . $this->table . '" (' . implode(', ', $defs) . ')';
    }

    /**
     * @return string[]
     */
    public function toIndexSql(): array
    {
        $sql = [];
        foreach ($this->indexes as $index) {
            $sql[] = sprintf(
                'CREATE %sINDEX IF NOT EXISTS "%s" ON "%s" (%s)',
                $index['unique'] ? 'UNIQUE ' : '',
                $index['name'],
                $this->table,
                implode(', ', array_map(static fn ($c) => '"' . $c . '"', $index['columns'])),
            );
        }

        return $sql;
    }
}
