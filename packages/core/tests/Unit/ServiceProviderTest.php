<?php

declare(strict_types=1);

namespace Nextphp\Core\Tests\Unit;

use Nextphp\Core\Container\AbstractServiceProvider;
use Nextphp\Core\Container\Container;
use Nextphp\Core\Container\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(Container::class)]
final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function registerIsCalledDuringBoot(): void
    {
        $container = new Container();
        $provider = new TrackingServiceProvider();

        $container->addServiceProvider($provider);
        $container->boot();

        self::assertTrue($provider->registered);
    }

    #[Test]
    public function bootIsCalledAfterRegister(): void
    {
        $container = new Container();
        $provider = new OrderTrackingServiceProvider();

        $container->addServiceProvider($provider);
        $container->boot();

        self::assertSame(['register', 'boot'], $provider->callOrder);
    }

    #[Test]
    public function bootIsIdempotent(): void
    {
        $container = new Container();
        $provider = new CountingServiceProvider();

        $container->addServiceProvider($provider);
        $container->boot();
        $container->boot();

        self::assertSame(1, $provider->registerCount);
        self::assertSame(1, $provider->bootCount);
    }

    #[Test]
    public function providerCanRegisterBindings(): void
    {
        $container = new Container();
        $container->addServiceProvider(new BindingServiceProvider());
        $container->boot();

        $result = $container->make(ProviderRegisteredService::class);

        self::assertInstanceOf(ProviderRegisteredService::class, $result);
    }

    #[Test]
    public function abstractProviderBootDoesNothing(): void
    {
        $container = new Container();
        $provider = new MinimalServiceProvider();
        $container->addServiceProvider($provider);

        // Should not throw
        $container->boot();

        self::assertTrue(true);
    }
}

// --- Test fixtures ---

final class TrackingServiceProvider extends AbstractServiceProvider
{
    public bool $registered = false;

    public bool $booted = false;

    public function register(ContainerInterface $container): void
    {
        $this->registered = true;
    }

    public function boot(ContainerInterface $container): void
    {
        $this->booted = true;
    }
}

final class OrderTrackingServiceProvider extends AbstractServiceProvider
{
    /** @var string[] */
    public array $callOrder = [];

    public function register(ContainerInterface $container): void
    {
        $this->callOrder[] = 'register';
    }

    public function boot(ContainerInterface $container): void
    {
        $this->callOrder[] = 'boot';
    }
}

final class CountingServiceProvider extends AbstractServiceProvider
{
    public int $registerCount = 0;

    public int $bootCount = 0;

    public function register(ContainerInterface $container): void
    {
        $this->registerCount++;
    }

    public function boot(ContainerInterface $container): void
    {
        $this->bootCount++;
    }
}

final class ProviderRegisteredService
{
}

final class BindingServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->bind(ProviderRegisteredService::class);
    }
}

final class MinimalServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
    }
}
