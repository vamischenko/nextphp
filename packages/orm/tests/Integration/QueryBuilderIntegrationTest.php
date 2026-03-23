<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Integration;

use Nextphp\Orm\Connection\Connection;
use Nextphp\Orm\Query\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builder::class)]
final class QueryBuilderIntegrationTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = Connection::sqlite();
        $this->db->statement(
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, age INTEGER, active INTEGER DEFAULT 1)',
        );

        // Seed data
        foreach ([
            ['Alice', 25, 1],
            ['Bob', 30, 1],
            ['Carol', 22, 0],
            ['Dave', 35, 1],
        ] as [$name, $age, $active]) {
            $this->db->insert('INSERT INTO users (name, age, active) VALUES (?, ?, ?)', [$name, $age, $active]);
        }
    }

    private function builder(): Builder
    {
        return (new Builder($this->db))->table('users');
    }

    #[Test]
    public function getAll(): void
    {
        $rows = $this->builder()->get();

        self::assertCount(4, $rows);
    }

    #[Test]
    public function first(): void
    {
        $row = $this->builder()->orderBy('id')->first();

        self::assertNotNull($row);
        self::assertSame('Alice', $row['name']);
    }

    #[Test]
    public function where(): void
    {
        $rows = $this->builder()->where('active', '=', 1)->get();

        self::assertCount(3, $rows);
    }

    #[Test]
    public function whereIn(): void
    {
        $rows = $this->builder()->whereIn('name', ['Alice', 'Bob'])->get();

        self::assertCount(2, $rows);
    }

    #[Test]
    public function countRows(): void
    {
        self::assertSame(4, $this->builder()->count());
        self::assertSame(3, $this->builder()->where('active', '=', 1)->count());
    }

    #[Test]
    public function exists(): void
    {
        self::assertTrue($this->builder()->where('name', '=', 'Alice')->exists());
        self::assertFalse($this->builder()->where('name', '=', 'Nobody')->exists());
    }

    #[Test]
    public function orderByAndLimit(): void
    {
        $rows = $this->builder()->orderByDesc('age')->limit(2)->get();

        self::assertCount(2, $rows);
        self::assertSame('Dave', $rows[0]['name']);
    }

    #[Test]
    public function insert(): void
    {
        $id = $this->builder()->insert(['name' => 'Eve', 'age' => 28, 'active' => 1]);

        self::assertNotFalse($id);
        self::assertSame(5, $this->builder()->count());
    }

    #[Test]
    public function update(): void
    {
        $affected = $this->builder()->where('name', '=', 'Alice')->update(['age' => 26]);

        self::assertSame(1, $affected);

        $row = $this->builder()->where('name', '=', 'Alice')->first();
        self::assertSame(26, (int) $row['age']);
    }

    #[Test]
    public function delete(): void
    {
        $affected = $this->builder()->where('name', '=', 'Carol')->delete();

        self::assertSame(1, $affected);
        self::assertSame(3, $this->builder()->count());
    }

    #[Test]
    public function groupByAndHaving(): void
    {
        $rows = $this->builder()
            ->select([Builder::raw('active, COUNT(*) as cnt')])
            ->groupBy('active')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        self::assertNotEmpty($rows);
    }

    #[Test]
    public function nestedWhere(): void
    {
        $rows = $this->builder()->where(function ($q) {
            $q->where('age', '<', 25)->orWhere('age', '>', 33);
        })->get();

        // Carol (22) and Dave (35)
        self::assertCount(2, $rows);
    }
}
