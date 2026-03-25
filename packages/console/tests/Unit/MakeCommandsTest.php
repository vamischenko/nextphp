<?php

declare(strict_types=1);

namespace Nextphp\Console\Tests\Unit;

use Nextphp\Console\Application;
use Nextphp\Console\Command\MakeControllerCommand;
use Nextphp\Console\Command\MakeMigrationCommand;
use Nextphp\Console\Command\MakeModelCommand;
use Nextphp\Console\Generator\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MakeControllerCommand::class)]
#[CoversClass(MakeModelCommand::class)]
#[CoversClass(MakeMigrationCommand::class)]
final class MakeCommandsTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . '/nextphp_make_' . uniqid();
        @mkdir($this->base, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);
    }

    #[Test]
    public function makeControllerViaApplication(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeControllerCommand(new Generator($this->base)));

        $code = $app->run(['nextphp', 'make:controller', 'Article']);
        self::assertSame(0, $code);
        self::assertFileExists($this->base . '/app/Http/Controllers/ArticleController.php');
    }

    #[Test]
    public function makeControllerAppendsControllerSuffix(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeControllerCommand(new Generator($this->base)));

        $app->run(['nextphp', 'make:controller', 'Comment']);
        self::assertFileExists($this->base . '/app/Http/Controllers/CommentController.php');
    }

    #[Test]
    public function makeControllerDoesNotDuplicateSuffix(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeControllerCommand(new Generator($this->base)));

        $app->run(['nextphp', 'make:controller', 'UserController']);
        self::assertFileExists($this->base . '/app/Http/Controllers/UserController.php');
        // Must not create UserControllerController
        self::assertFileDoesNotExist($this->base . '/app/Http/Controllers/UserControllerController.php');
    }

    #[Test]
    public function makeControllerFailsWithoutName(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeControllerCommand(new Generator($this->base)));

        $code = $app->run(['nextphp', 'make:controller']);
        self::assertSame(1, $code);
    }

    #[Test]
    public function makeModelViaApplication(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeModelCommand(new Generator($this->base)));

        $code = $app->run(['nextphp', 'make:model', 'Order']);
        self::assertSame(0, $code);
        self::assertFileExists($this->base . '/app/Models/Order.php');
    }

    #[Test]
    public function makeModelWithMigrationFlag(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeModelCommand(new Generator($this->base)));

        $code = $app->run(['nextphp', 'make:model', 'Invoice', '--migration']);
        self::assertSame(0, $code);
        self::assertFileExists($this->base . '/app/Models/Invoice.php');

        // Migration file must exist somewhere in database/migrations/
        $files = glob($this->base . '/database/migrations/*invoices*.php') ?: [];
        self::assertNotEmpty($files, 'Migration file for Invoice not found');
    }

    #[Test]
    public function makeMigrationViaApplication(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeMigrationCommand(new Generator($this->base)));

        $code  = $app->run(['nextphp', 'make:migration', 'create_tags_table']);
        self::assertSame(0, $code);

        $files = glob($this->base . '/database/migrations/*create_tags_table.php') ?: [];
        self::assertNotEmpty($files);
    }

    #[Test]
    public function makeMigrationNormalisesName(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeMigrationCommand(new Generator($this->base)));

        $app->run(['nextphp', 'make:migration', 'Create Tags Table']);
        $files = glob($this->base . '/database/migrations/*create_tags_table.php') ?: [];
        self::assertNotEmpty($files);
    }

    #[Test]
    public function makeMigrationFailsWithoutName(): void
    {
        $app = $this->makeApp();
        $app->add(new MakeMigrationCommand(new Generator($this->base)));

        $code = $app->run(['nextphp', 'make:migration']);
        self::assertSame(1, $code);
    }

    private function makeApp(): Application
    {
        return new Application();
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
