<?php

declare(strict_types=1);

namespace Nextphp\Core\Tests\Unit;

use Nextphp\Core\Container\Binding;
use Nextphp\Core\Container\BindingType;
use Nextphp\Core\Container\CompilerPassInterface;
use Nextphp\Core\Container\Container;
use Nextphp\Core\Container\ContainerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CompilerPassTest extends TestCase
{
    #[Test]
    public function passIsExecutedDuringBoot(): void
    {
        $container = new Container();
        $executed  = false;

        $container->addCompilerPass(new class ($executed) implements CompilerPassInterface {
            public function __construct(private bool &$executed) {}

            public function process(ContainerInterface $container, array &$bindings): void
            {
                $this->executed = true;
            }
        });

        $container->boot();

        self::assertTrue($executed);
    }

    #[Test]
    public function passCanReplaceBinding(): void
    {
        $container = new Container();

        $container->bind('service', fn () => new \stdClass());

        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerInterface $container, array &$bindings): void
            {
                if (isset($bindings['service'])) {
                    $replacement       = new \stdClass();
                    $replacement->tag  = 'replaced';
                    $bindings['service'] = new Binding(
                        BindingType::Instance,
                        null,
                        $replacement,
                    );
                }
            }
        });

        $container->boot();

        /** @var \stdClass $svc */
        $svc = $container->make('service');
        self::assertSame('replaced', $svc->tag);
    }

    #[Test]
    public function passRunsAfterProvidersRegisterButBeforeProvidersBoot(): void
    {
        $container = new Container();
        $log       = [];

        $container->addServiceProvider(new class ($log) extends \Nextphp\Core\Container\AbstractServiceProvider {
            /** @param list<string> $log */
            public function __construct(private array &$log) {}

            public function register(ContainerInterface $c): void
            {
                $this->log[] = 'register';
            }

            public function boot(ContainerInterface $c): void
            {
                $this->log[] = 'boot';
            }
        });

        $container->addCompilerPass(new class ($log) implements CompilerPassInterface {
            /** @param list<string> $log */
            public function __construct(private array &$log) {}

            public function process(ContainerInterface $container, array &$bindings): void
            {
                $this->log[] = 'pass';
            }
        });

        $container->boot();

        self::assertSame(['register', 'pass', 'boot'], $log);
    }

    #[Test]
    public function multiplePassesRunInOrder(): void
    {
        $container = new Container();
        $log       = [];

        for ($i = 1; $i <= 3; $i++) {
            $container->addCompilerPass(new class ($log, $i) implements CompilerPassInterface {
                /** @param list<string> $log */
                public function __construct(private array &$log, private int $n) {}

                public function process(ContainerInterface $container, array &$bindings): void
                {
                    $this->log[] = "pass{$this->n}";
                }
            });
        }

        $container->boot();

        self::assertSame(['pass1', 'pass2', 'pass3'], $log);
    }

    #[Test]
    public function passNotCalledIfBootAlreadyCalled(): void
    {
        $container = new Container();
        $container->boot();

        $called = false;
        $container->addCompilerPass(new class ($called) implements CompilerPassInterface {
            public function __construct(private bool &$called) {}

            public function process(ContainerInterface $container, array &$bindings): void
            {
                $this->called = true;
            }
        });

        $container->boot(); // second call — noop

        self::assertFalse($called);
    }
}
