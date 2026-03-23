<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Integration;

use Nextphp\Orm\Connection\Connection;
use Nextphp\Orm\Exception\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Connection::class)]
final class ConnectionTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = Connection::sqlite();
        $this->db->statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT)');
    }

    #[Test]
    public function insertAndSelect(): void
    {
        $id = $this->db->insert('INSERT INTO users (name, email) VALUES (?, ?)', ['Alice', 'alice@example.com']);

        self::assertNotFalse($id);

        $row = $this->db->selectOne('SELECT * FROM users WHERE id = ?', [$id]);

        self::assertNotNull($row);
        self::assertSame('Alice', $row['name']);
    }

    #[Test]
    public function selectAll(): void
    {
        $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Alice']);
        $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Bob']);

        $rows = $this->db->select('SELECT * FROM users');

        self::assertCount(2, $rows);
    }

    #[Test]
    public function affectingStatement(): void
    {
        $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Alice']);

        $affected = $this->db->affectingStatement('UPDATE users SET name = ? WHERE name = ?', ['Alicia', 'Alice']);

        self::assertSame(1, $affected);
    }

    #[Test]
    public function deleteStatement(): void
    {
        $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Bob']);

        $affected = $this->db->affectingStatement('DELETE FROM users WHERE name = ?', ['Bob']);

        self::assertSame(1, $affected);
    }

    #[Test]
    public function transaction(): void
    {
        $this->db->transaction(function () {
            $this->db->insert('INSERT INTO users (name) VALUES (?)', ['TX User']);
        });

        $row = $this->db->selectOne('SELECT * FROM users WHERE name = ?', ['TX User']);

        self::assertNotNull($row);
    }

    #[Test]
    public function transactionRollsBackOnException(): void
    {
        try {
            $this->db->transaction(function () {
                $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Rollback']);
                throw new \RuntimeException('forced');
            });
        } catch (\RuntimeException) {
        }

        $row = $this->db->selectOne('SELECT * FROM users WHERE name = ?', ['Rollback']);

        self::assertNull($row);
    }

    #[Test]
    public function queryLog(): void
    {
        $this->db->enableQueryLog();
        $this->db->select('SELECT * FROM users');

        $log = $this->db->getQueryLog();

        self::assertCount(1, $log);
        self::assertArrayHasKey('sql', $log[0]);
        self::assertArrayHasKey('time', $log[0]);
    }

    #[Test]
    public function throwsQueryExceptionOnBadSql(): void
    {
        $this->expectException(QueryException::class);

        $this->db->select('SELECT * FROM nonexistent_table_xyz');
    }

    #[Test]
    public function nestedTransactionUsingSavepoints(): void
    {
        $this->db->beginTransaction();
        $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Outer']);

        $this->db->beginTransaction();
        $this->db->insert('INSERT INTO users (name) VALUES (?)', ['Inner']);
        $this->db->rollBack(); // rollback inner

        $this->db->commit(); // commit outer

        $outer = $this->db->selectOne('SELECT * FROM users WHERE name = ?', ['Outer']);
        $inner = $this->db->selectOne('SELECT * FROM users WHERE name = ?', ['Inner']);

        self::assertNotNull($outer);
        self::assertNull($inner);
    }
}
