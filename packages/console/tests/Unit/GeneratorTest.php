<?php

declare(strict_types=1);

namespace Nextphp\Console\Tests\Unit;

use Nextphp\Console\Generator\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Generator::class)]
final class GeneratorTest extends TestCase
{
    #[Test]
    public function createsControllerModelAndMigration(): void
    {
        $base = sys_get_temp_dir() . '/nextphp_console_gen';
        @mkdir($base, 0777, true);

        $generator = new Generator($base);

        $controller = $generator->makeController('UserController');
        $model = $generator->makeModel('User');
        $migration = $generator->makeMigration('create_users_table');

        self::assertFileExists($controller);
        self::assertFileExists($model);
        self::assertFileExists($migration);
    }
}
