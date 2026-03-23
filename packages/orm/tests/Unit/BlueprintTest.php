<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Schema\Blueprint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Blueprint::class)]
final class BlueprintTest extends TestCase
{
    #[Test]
    public function createTableSqlWithId(): void
    {
        $bp = new Blueprint('users');
        $bp->id();

        $sql = $bp->toCreateSql();

        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS', $sql);
        self::assertStringContainsString('"id"', $sql);
        self::assertStringContainsString('AUTOINCREMENT', $sql);
    }

    #[Test]
    public function createTableWithColumns(): void
    {
        $bp = new Blueprint('users');
        $bp->id();
        $bp->string('name');
        $bp->string('email')->unique();
        $bp->timestamps();

        $sql = $bp->toCreateSql();

        self::assertStringContainsString('"name"', $sql);
        self::assertStringContainsString('"email"', $sql);
    }

    #[Test]
    public function dropSql(): void
    {
        $bp = new Blueprint('users');

        self::assertSame('DROP TABLE IF EXISTS "users"', $bp->toDropSql());
    }

    #[Test]
    public function indexSql(): void
    {
        $bp = new Blueprint('users');
        $bp->string('email')->unique();

        $sqls = $bp->toIndexSql();

        self::assertNotEmpty($sqls);
        self::assertStringContainsString('UNIQUE INDEX', $sqls[0]);
    }

    #[Test]
    public function nullableColumn(): void
    {
        $bp = new Blueprint('posts');
        $bp->string('excerpt')->nullable();

        $sql = $bp->toCreateSql();

        self::assertStringNotContainsString('NOT NULL', $sql);
    }

    #[Test]
    public function defaultValue(): void
    {
        $bp = new Blueprint('posts');
        $bp->boolean('active')->default(1);

        $sql = $bp->toCreateSql();

        self::assertStringContainsString('DEFAULT 1', $sql);
    }

    #[Test]
    public function softDeletes(): void
    {
        $bp = new Blueprint('posts');
        $bp->softDeletes();

        $sql = $bp->toCreateSql();

        self::assertStringContainsString('deleted_at', $sql);
    }
}
