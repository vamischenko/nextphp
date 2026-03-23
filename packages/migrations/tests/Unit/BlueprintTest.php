<?php

declare(strict_types=1);

namespace Nextphp\Migrations\Tests\Unit;

use Nextphp\Migrations\Schema\Blueprint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Blueprint::class)]
final class BlueprintTest extends TestCase
{
    #[Test]
    public function createSqlContainsColumns(): void
    {
        $bp = new Blueprint('users');
        $bp->id();
        $bp->string('email')->unique();
        $sql = $bp->toCreateSql();

        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS', $sql);
        self::assertStringContainsString('"email"', $sql);
        self::assertNotEmpty($bp->toIndexSql());
    }
}
