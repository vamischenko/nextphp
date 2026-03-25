<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Pagination\Paginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Paginator::class)]
final class PaginatorTest extends TestCase
{
    /** @return list<array<string,mixed>> */
    private function rows(int $count): array
    {
        $result = [];
        for ($i = 1; $i <= $count; $i++) {
            $result[] = ['id' => $i];
        }
        return $result;
    }

    #[Test]
    public function basicMeta(): void
    {
        $p = new Paginator($this->rows(10), total: 95, perPage: 10, currentPage: 3);

        self::assertSame(10, $p->perPage());
        self::assertSame(95, $p->total());
        self::assertSame(3,  $p->currentPage());
        self::assertSame(10, $p->lastPage());
        self::assertSame(10, $p->count());
    }

    #[Test]
    public function fromAndTo(): void
    {
        $p = new Paginator($this->rows(10), total: 95, perPage: 10, currentPage: 2);
        self::assertSame(11, $p->from());
        self::assertSame(20, $p->to());
    }

    #[Test]
    public function fromAndToOnLastPage(): void
    {
        $p = new Paginator($this->rows(5), total: 25, perPage: 10, currentPage: 3);
        self::assertSame(21, $p->from());
        self::assertSame(25, $p->to());
    }

    #[Test]
    public function hasMorePages(): void
    {
        $full = new Paginator($this->rows(10), total: 30, perPage: 10, currentPage: 2);
        self::assertTrue($full->hasMorePages());

        $last = new Paginator($this->rows(10), total: 30, perPage: 10, currentPage: 3);
        self::assertFalse($last->hasMorePages());
    }

    #[Test]
    public function previousAndNextPage(): void
    {
        $p = new Paginator($this->rows(10), total: 30, perPage: 10, currentPage: 2);
        self::assertSame(1, $p->previousPage());
        self::assertSame(3, $p->nextPage());
    }

    #[Test]
    public function firstPageHasNoPrevious(): void
    {
        $p = new Paginator($this->rows(10), total: 30, perPage: 10, currentPage: 1);
        self::assertNull($p->previousPage());
        self::assertTrue($p->onFirstPage());
    }

    #[Test]
    public function lastPageHasNoNext(): void
    {
        $p = new Paginator($this->rows(10), total: 30, perPage: 10, currentPage: 3);
        self::assertNull($p->nextPage());
    }

    #[Test]
    public function emptyResults(): void
    {
        $p = new Paginator([], total: 0, perPage: 10, currentPage: 1);
        self::assertTrue($p->isEmpty());
        self::assertFalse($p->isNotEmpty());
        self::assertSame(0, $p->from());
        self::assertSame(0, $p->to());
        self::assertSame(1, $p->lastPage());
    }

    #[Test]
    public function toArray(): void
    {
        $p    = new Paginator($this->rows(5), total: 25, perPage: 10, currentPage: 3);
        $arr  = $p->toArray();

        self::assertSame(3,  $arr['current_page']);
        self::assertSame(10, $arr['per_page']);
        self::assertSame(25, $arr['total']);
        self::assertSame(3,  $arr['last_page']);
        self::assertFalse($arr['has_more']);
    }

    #[Test]
    public function toJson(): void
    {
        $p    = new Paginator($this->rows(2), total: 2, perPage: 10, currentPage: 1);
        $json = json_decode($p->toJson(), true);
        self::assertIsArray($json);
        self::assertArrayHasKey('data', $json);
        self::assertCount(2, $json['data']);
    }

    #[Test]
    public function builderPaginateIntegration(): void
    {
        // Test Builder::paginate() SQL generation via mock connection
        $driver     = new \Nextphp\Orm\Connection\Driver\SqliteDriver();
        $connection = $this->createMock(\Nextphp\Orm\Connection\SqlConnectionInterface::class);
        $connection->method('getGrammar')->willReturn($driver);
        $connection->method('getDriverName')->willReturn('sqlite');

        // selectOne for COUNT, select for items
        $connection->method('selectOne')->willReturn(['aggregate' => 95]);
        $connection->method('select')->willReturn(array_fill(0, 10, ['id' => 1]));

        $paginator = (new \Nextphp\Orm\Query\Builder($connection))
            ->table('users')
            ->paginate(10, 3);

        self::assertInstanceOf(Paginator::class, $paginator);
        self::assertSame(95, $paginator->total());
        self::assertSame(3,  $paginator->currentPage());
        self::assertCount(10, $paginator->items());
    }
}
