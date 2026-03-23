<?php

declare(strict_types=1);

namespace Nextphp\Core\Tests\Unit;

use Nextphp\Core\Container\Container;
use Nextphp\Core\Container\ContainerInterface;
use Nextphp\Core\Exception\ContainerException;
use Nextphp\Core\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    #[Test]
    public function bindAndMakeTransient(): void
    {
        $this->container->bind(SimpleService::class);

        $a = $this->container->make(SimpleService::class);
        $b = $this->container->make(SimpleService::class);

        self::assertInstanceOf(SimpleService::class, $a);
        self::assertNotSame($a, $b);
    }

    #[Test]
    public function singletonReturnsSameInstance(): void
    {
        $this->container->singleton(SimpleService::class);

        $a = $this->container->make(SimpleService::class);
        $b = $this->container->make(SimpleService::class);

        self::assertSame($a, $b);
    }

    #[Test]
    public function scopedReturnsSameInstanceWithinScope(): void
    {
        $this->container->scoped(SimpleService::class);

        $a = $this->container->make(SimpleService::class);
        $b = $this->container->make(SimpleService::class);

        self::assertSame($a, $b);
    }

    #[Test]
    public function scopedResetsAfterFlush(): void
    {
        $this->container->scoped(SimpleService::class);

        $a = $this->container->make(SimpleService::class);
        $this->container->flushScoped();
        $b = $this->container->make(SimpleService::class);

        self::assertNotSame($a, $b);
    }

    #[Test]
    public function instanceBindingReturnsSameObject(): void
    {
        $service = new SimpleService();
        $this->container->instance(SimpleService::class, $service);

        self::assertSame($service, $this->container->make(SimpleService::class));
        self::assertSame($service, $this->container->make(SimpleService::class));
    }

    #[Test]
    public function bindWithClosure(): void
    {
        $this->container->bind(SimpleService::class, fn () => new SimpleService());

        $result = $this->container->make(SimpleService::class);

        self::assertInstanceOf(SimpleService::class, $result);
    }

    #[Test]
    public function bindInterfaceToConcreteClass(): void
    {
        $this->container->bind(ServiceInterface::class, ConcreteService::class);

        $result = $this->container->make(ServiceInterface::class);

        self::assertInstanceOf(ConcreteService::class, $result);
    }

    #[Test]
    public function autowiringResolvesNestedDependencies(): void
    {
        $result = $this->container->make(ServiceWithDependency::class);

        self::assertInstanceOf(ServiceWithDependency::class, $result);
        self::assertInstanceOf(SimpleService::class, $result->service);
    }

    #[Test]
    public function makeWithExplicitParameters(): void
    {
        $service = new SimpleService();
        $result = $this->container->make(ServiceWithDependency::class, ['service' => $service]);

        self::assertSame($service, $result->service);
    }

    #[Test]
    public function callWithClosure(): void
    {
        $result = $this->container->call(fn (SimpleService $s) => $s);

        self::assertInstanceOf(SimpleService::class, $result);
    }

    #[Test]
    public function callWithArrayCallable(): void
    {
        $obj = new CallableService();
        $result = $this->container->call([$obj, 'handle']);

        self::assertSame('handled', $result);
    }

    #[Test]
    public function psr11GetReturnsInstance(): void
    {
        $this->container->bind(SimpleService::class);

        $result = $this->container->get(SimpleService::class);

        self::assertInstanceOf(SimpleService::class, $result);
    }

    #[Test]
    public function psr11HasReturnsTrueForBound(): void
    {
        $this->container->bind(SimpleService::class);

        self::assertTrue($this->container->has(SimpleService::class));
    }

    #[Test]
    public function psr11HasReturnsFalseForUnbound(): void
    {
        self::assertFalse($this->container->has('UnknownClass'));
    }

    #[Test]
    public function getThrowsNotFoundExceptionForUnresolvable(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('NonExistentClass\That\Cannot\Be\Autowired');
    }

    #[Test]
    public function boundReturnsTrueAfterBinding(): void
    {
        $this->container->bind(SimpleService::class);

        self::assertTrue($this->container->bound(SimpleService::class));
    }

    #[Test]
    public function boundReturnsFalseForUnbound(): void
    {
        self::assertFalse($this->container->bound(SimpleService::class));
    }

    #[Test]
    public function containerBindsItselfOnConstruction(): void
    {
        $result = $this->container->make(ContainerInterface::class);

        self::assertSame($this->container, $result);
    }

    #[Test]
    public function makeNonInstantiableThrowsContainerException(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->make(ServiceInterface::class);
    }
}

// --- Test fixtures ---

interface ServiceInterface
{
}

final class SimpleService
{
}

final class ConcreteService implements ServiceInterface
{
}

final class ServiceWithDependency
{
    public function __construct(
        public readonly SimpleService $service,
    ) {
    }
}

final class CallableService
{
    public function handle(): string
    {
        return 'handled';
    }
}
