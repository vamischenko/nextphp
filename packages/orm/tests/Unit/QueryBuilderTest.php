<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Connection\Driver\SqliteDriver;
use Nextphp\Orm\Connection\SqlConnectionInterface;
use Nextphp\Orm\Query\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SQL compilation tests only — no real DB connection needed.
 */
#[CoversClass(Builder::class)]
final class QueryBuilderTest extends TestCase
{
    private Builder $builder;

    protected function setUp(): void
    {
        $driver     = new SqliteDriver();
        $connection = $this->createMock(SqlConnectionInterface::class);
        $connection->method('getGrammar')->willReturn($driver);
        $connection->method('getDriverName')->willReturn('sqlite');

        $this->builder = (new Builder($connection))->table('users');
    }

    #[Test]
    public function basicSelect(): void
    {
        self::assertSame('SELECT * FROM users', $this->builder->toSql());
    }

    #[Test]
    public function selectColumns(): void
    {
        $sql = (clone $this->builder)->select(['id', 'name'])->toSql();
        self::assertSame('SELECT id, name FROM users', $sql);
    }

    #[Test]
    public function distinct(): void
    {
        self::assertStringContainsString('SELECT DISTINCT', (clone $this->builder)->distinct()->toSql());
    }

    #[Test]
    public function whereEquals(): void
    {
        $b = (clone $this->builder)->where('id', '=', 1);
        self::assertSame('SELECT * FROM users WHERE id = ?', $b->toSql());
        self::assertSame([1], $b->getBindings());
    }

    #[Test]
    public function orWhere(): void
    {
        $b = (clone $this->builder)->where('name', '=', 'Alice')->orWhere('name', '=', 'Bob');
        self::assertSame('SELECT * FROM users WHERE name = ? OR name = ?', $b->toSql());
    }

    #[Test]
    public function whereIn(): void
    {
        $b = (clone $this->builder)->whereIn('id', [1, 2, 3]);
        self::assertSame('SELECT * FROM users WHERE id IN (?, ?, ?)', $b->toSql());
        self::assertSame([1, 2, 3], $b->getBindings());
    }

    #[Test]
    public function whereNotIn(): void
    {
        self::assertStringContainsString('NOT IN', (clone $this->builder)->whereNotIn('id', [1, 2])->toSql());
    }

    #[Test]
    public function whereInEmpty(): void
    {
        self::assertStringContainsString('0 = 1', (clone $this->builder)->whereIn('id', [])->toSql());
    }

    #[Test]
    public function whereNull(): void
    {
        self::assertStringContainsString('IS NULL', (clone $this->builder)->whereNull('deleted_at')->toSql());
    }

    #[Test]
    public function whereNotNull(): void
    {
        self::assertStringContainsString('IS NOT NULL', (clone $this->builder)->whereNotNull('email')->toSql());
    }

    #[Test]
    public function whereBetween(): void
    {
        $b = (clone $this->builder)->whereBetween('age', 18, 65);
        self::assertStringContainsString('BETWEEN', $b->toSql());
        self::assertSame([18, 65], $b->getBindings());
    }

    #[Test]
    public function whereNested(): void
    {
        $b = (clone $this->builder)->where(function ($q) {
            $q->where('a', '=', 1)->orWhere('b', '=', 2);
        });
        self::assertStringContainsString('(a = ? OR b = ?)', $b->toSql());
    }

    #[Test]
    public function whereRaw(): void
    {
        self::assertStringContainsString('LENGTH(name) > ?', (clone $this->builder)->whereRaw('LENGTH(name) > ?', 3)->toSql());
    }

    #[Test]
    public function joinClause(): void
    {
        self::assertStringContainsString('INNER JOIN posts ON', (clone $this->builder)->join('posts', 'users.id', '=', 'posts.user_id')->toSql());
    }

    #[Test]
    public function leftJoin(): void
    {
        self::assertStringContainsString('LEFT JOIN', (clone $this->builder)->leftJoin('posts', 'users.id', '=', 'posts.user_id')->toSql());
    }

    #[Test]
    public function orderBy(): void
    {
        self::assertStringContainsString('ORDER BY name ASC', (clone $this->builder)->orderBy('name')->toSql());
    }

    #[Test]
    public function orderByDesc(): void
    {
        self::assertStringContainsString('ORDER BY created_at DESC', (clone $this->builder)->orderByDesc('created_at')->toSql());
    }

    #[Test]
    public function groupBy(): void
    {
        self::assertStringContainsString('GROUP BY status', (clone $this->builder)->groupBy('status')->toSql());
    }

    #[Test]
    public function having(): void
    {
        self::assertStringContainsString('HAVING', (clone $this->builder)->groupBy('status')->having('count(*)', '>', 5)->toSql());
    }

    #[Test]
    public function limitOffset(): void
    {
        $sql = (clone $this->builder)->limit(10)->offset(20)->toSql();
        self::assertStringContainsString('LIMIT 10', $sql);
        self::assertStringContainsString('OFFSET 20', $sql);
    }

    #[Test]
    public function takeSkipAliases(): void
    {
        $sql = (clone $this->builder)->take(5)->skip(10)->toSql();
        self::assertStringContainsString('LIMIT 5', $sql);
        self::assertStringContainsString('OFFSET 10', $sql);
    }

    #[Test]
    public function rawExpression(): void
    {
        self::assertStringContainsString('COUNT(*) as total', (clone $this->builder)->select([Builder::raw('COUNT(*) as total')])->toSql());
    }

    #[Test]
    public function addSelect(): void
    {
        $sql = (clone $this->builder)->select(['id'])->addSelect('name')->toSql();
        self::assertStringContainsString('id', $sql);
        self::assertStringContainsString('name', $sql);
    }
}
