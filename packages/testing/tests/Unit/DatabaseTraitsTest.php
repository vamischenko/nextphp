<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Orm\Connection\Connection;
use Nextphp\Testing\Database\DatabaseTransactions;
use Nextphp\Testing\Database\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class DatabaseTraitsTest extends TestCase
{
    #[Test]
    public function refreshAndTransactionHelpersWork(): void
    {
        $helper = new class () {
            use RefreshDatabase;
            use DatabaseTransactions;

            public function refresh(Connection $connection): void
            {
                $this->refreshSqliteSchema($connection, ['users']);
            }
            public function begin(Connection $connection): void
            {
                $this->beginTransaction($connection);
            }
            public function rollback(Connection $connection): void
            {
                $this->rollbackTransaction($connection);
            }
        };

        $db = Connection::sqlite();
        $db->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $helper->refresh($db);
        $db->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $helper->begin($db);
        $db->insert('INSERT INTO users (id, name) VALUES (?, ?)', [1, 'A']);
        $helper->rollback($db);
        $row = $db->selectOne('SELECT * FROM users WHERE id = ?', [1]);

        self::assertNull($row);
    }
}
