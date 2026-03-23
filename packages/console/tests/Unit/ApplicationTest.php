<?php

declare(strict_types=1);

namespace Nextphp\Console\Tests\Unit;

use Nextphp\Console\Application;
use Nextphp\Console\Command;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
final class ApplicationTest extends TestCase
{
    #[Test]
    public function runsRegisteredCommand(): void
    {
        $app = new Application();
        $command = new class () extends Command {
            public array $received = [];
            public function __construct()
            {
                parent::__construct('demo:run', 'Demo command');
            }
            public function handle(array $arguments, array $options = []): int
            {
                $this->received = $arguments;

                return 0;
            }
        };

        $app->add($command);
        $code = $app->run(['nextphp', 'demo:run', 'a', 'b']);

        self::assertSame(0, $code);
        self::assertSame(['a', 'b'], $command->received);
        self::assertTrue($app->has('demo:run'));
    }

    #[Test]
    public function parsesOptionsFromInput(): void
    {
        $app = new Application();
        $command = new class () extends Command {
            public array $options = [];
            public function __construct()
            {
                parent::__construct('demo:opts', 'Options');
            }
            public function handle(array $arguments, array $options = []): int
            {
                $this->options = $options;

                return 0;
            }
        };

        $app->add($command);
        $code = $app->run(['nextphp', 'demo:opts', '--env=prod', '--force']);

        self::assertSame(0, $code);
        self::assertSame(['env' => 'prod', 'force' => true], $command->options);
    }
}
