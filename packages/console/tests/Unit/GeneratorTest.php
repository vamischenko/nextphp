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
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . '/nextphp_console_gen_' . uniqid();
        @mkdir($this->base, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);
    }

    #[Test]
    public function createsController(): void
    {
        $generator = new Generator($this->base);
        $path      = $generator->makeController('UserController');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertStringContainsString('class UserController', $content);
        self::assertStringContainsString('namespace App\Http\Controllers', $content);
        self::assertStringContainsString('ServerRequestInterface', $content);
    }

    #[Test]
    public function createsModel(): void
    {
        $generator = new Generator($this->base);
        $path      = $generator->makeModel('User');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertStringContainsString('class User extends Model', $content);
        self::assertStringContainsString('namespace App\Models', $content);
        self::assertStringContainsString("'users'", $content); // table name
    }

    #[Test]
    public function createsMigrationWithTimestamp(): void
    {
        $generator = new Generator($this->base);
        $path      = $generator->makeMigration('create_posts_table');

        self::assertFileExists($path);
        $content  = file_get_contents($path);
        $filename = basename($path);

        // filename starts with timestamp: YYYY_MM_DD_HHmmss_
        self::assertMatchesRegularExpression('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $filename);
        self::assertStringContainsString('extends Migration', $content);
        self::assertStringContainsString("'posts'", $content); // guessed table
    }

    #[Test]
    public function migrationGuessesTableFromName(): void
    {
        $generator = new Generator($this->base);

        $path    = $generator->makeMigration('create_orders_table');
        $content = file_get_contents($path);
        self::assertStringContainsString("'orders'", $content);

        $path2    = $generator->makeMigration('add_status_to_invoices');
        $content2 = file_get_contents($path2);
        self::assertStringContainsString("'invoices'", $content2);
    }

    #[Test]
    public function migrationClassIsStudlyCase(): void
    {
        $generator = new Generator($this->base);
        $path      = $generator->makeMigration('create_user_profiles_table');
        $content   = file_get_contents($path);
        self::assertStringContainsString('class CreateUserProfilesTable', $content);
    }

    #[Test]
    public function createsControllerModelAndMigration(): void
    {
        $generator  = new Generator($this->base);
        $controller = $generator->makeController('PostController');
        $model      = $generator->makeModel('Post');
        $migration  = $generator->makeMigration('create_posts_table');

        self::assertFileExists($controller);
        self::assertFileExists($model);
        self::assertFileExists($migration);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
