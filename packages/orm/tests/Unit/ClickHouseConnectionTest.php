<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Connection\ClickHouseConnection;
use Nextphp\Orm\Connection\HttpClientInterface;
use Nextphp\Orm\Exception\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClickHouseConnection::class)]
final class ClickHouseConnectionTest extends TestCase
{
    private HttpClientInterface $http;

    private ClickHouseConnection $conn;

    protected function setUp(): void
    {
        $this->http = $this->createMock(HttpClientInterface::class);
        $this->conn = new ClickHouseConnection('localhost', 'testdb', 'default', '', 8123, $this->http);
    }

    #[Test]
    public function driverNameIsClickHouse(): void
    {
        self::assertSame('clickhouse', $this->conn->getDriverName());
    }

    #[Test]
    public function selectParsesJsonEachRowResponse(): void
    {
        $this->http->method('post')->willReturn(
            '{"id":1,"name":"Alice"}' . "\n" .
            '{"id":2,"name":"Bob"}' . "\n",
        );

        $rows = $this->conn->select('SELECT * FROM users');

        self::assertCount(2, $rows);
        self::assertSame('Alice', $rows[0]['name']);
        self::assertSame('Bob', $rows[1]['name']);
    }

    #[Test]
    public function selectOneReturnsFirstRow(): void
    {
        $this->http->method('post')->willReturn('{"id":1,"name":"Alice"}' . "\n");

        $row = $this->conn->selectOne('SELECT * FROM users LIMIT 1');

        self::assertNotNull($row);
        self::assertSame('Alice', $row['name']);
    }

    #[Test]
    public function selectOneReturnsNullOnEmptyResponse(): void
    {
        $this->http->method('post')->willReturn('');

        $row = $this->conn->selectOne('SELECT * FROM users WHERE id = 999');

        self::assertNull($row);
    }

    #[Test]
    public function insertReturnsZeroId(): void
    {
        $this->http->method('post')->willReturn('');

        $id = $this->conn->insert('INSERT INTO users (name) VALUES (?)', ['Alice']);

        self::assertSame('0', $id);
    }

    #[Test]
    public function affectingStatementReturnsOne(): void
    {
        $this->http->method('post')->willReturn('');

        $affected = $this->conn->affectingStatement('ALTER TABLE users DELETE WHERE name = ?', ['Alice']);

        self::assertSame(1, $affected);
    }

    #[Test]
    public function statementReturnsTrueOnSuccess(): void
    {
        $this->http->method('post')->willReturn('');

        $result = $this->conn->statement('CREATE TABLE test (id UInt64) ENGINE = Memory');

        self::assertTrue($result);
    }

    #[Test]
    public function throwsQueryExceptionOnHttpFailure(): void
    {
        $this->http->method('post')->willThrowException(
            new \RuntimeException('Connection refused'),
        );

        $this->expectException(QueryException::class);

        $this->conn->select('SELECT 1');
    }

    #[Test]
    public function queryLogRecordsEntries(): void
    {
        $this->http->method('post')->willReturn('{"x":1}');

        $this->conn->enableQueryLog();
        $this->conn->select('SELECT 1');

        $log = $this->conn->getQueryLog();

        self::assertCount(1, $log);
        self::assertArrayHasKey('sql', $log[0]);
        self::assertArrayHasKey('time', $log[0]);
    }

    #[Test]
    public function flushQueryLogClearsEntries(): void
    {
        $this->http->method('post')->willReturn('{"x":1}');

        $this->conn->enableQueryLog();
        $this->conn->select('SELECT 1');
        $this->conn->flushQueryLog();

        self::assertCount(0, $this->conn->getQueryLog());
    }

    #[Test]
    public function bindingsAreInterpolatedIntoSql(): void
    {
        $captured = '';

        $this->http
            ->expects(self::once())
            ->method('post')
            ->willReturnCallback(function (string $url, string $body) use (&$captured): string {
                $captured = $body;

                return '';
            });

        $this->conn->statement('INSERT INTO t (name, age) VALUES (?, ?)', ['Alice', 25]);

        self::assertStringContainsString("'Alice'", $captured);
        self::assertStringContainsString('25', $captured);
    }

    #[Test]
    public function nullBindingInterpolatedAsNull(): void
    {
        $captured = '';

        $this->http
            ->expects(self::once())
            ->method('post')
            ->willReturnCallback(function (string $url, string $body) use (&$captured): string {
                $captured = $body;

                return '';
            });

        $this->conn->statement('INSERT INTO t (x) VALUES (?)', [null]);

        self::assertStringContainsString('NULL', $captured);
    }

    #[Test]
    public function transactionCallbackIsExecuted(): void
    {
        $called = false;

        $this->conn->transaction(function () use (&$called): void {
            $called = true;
        });

        self::assertTrue($called);
    }

    #[Test]
    public function transactionDoesNotSuppressExceptions(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->conn->transaction(function (): void {
            throw new \RuntimeException('test');
        });
    }
}
