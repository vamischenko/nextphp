<?php

declare(strict_types=1);

namespace Nextphp\Core\Tests\Unit;

use Nextphp\Core\Attributes\Inject;
use Nextphp\Core\Container\Container;
use Nextphp\Core\Container\Resolver\ReflectionResolver;
use Nextphp\Core\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReflectionResolver::class)]
final class ReflectionResolverTest extends TestCase
{
    private Container $container;

    private ReflectionResolver $resolver;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->resolver = new ReflectionResolver($this->container);
    }

    #[Test]
    public function resolvesClassWithNoConstructor(): void
    {
        $result = $this->resolver->resolve(NoConstructorClass::class);

        self::assertInstanceOf(NoConstructorClass::class, $result);
    }

    #[Test]
    public function resolvesClassWithTypedDependency(): void
    {
        $result = $this->resolver->resolve(ClassWithTypedDep::class);

        self::assertInstanceOf(ClassWithTypedDep::class, $result);
        self::assertInstanceOf(NoConstructorClass::class, $result->dep);
    }

    #[Test]
    public function resolvesWithDefaultParameterValue(): void
    {
        $result = $this->resolver->resolve(ClassWithDefaultParam::class);

        self::assertSame('default', $result->value);
    }

    #[Test]
    public function resolvesWithExplicitParameterOverride(): void
    {
        $result = $this->resolver->resolve(ClassWithDefaultParam::class, ['value' => 'custom']);

        self::assertSame('custom', $result->value);
    }

    #[Test]
    public function resolvesWithInjectAttribute(): void
    {
        $this->container->bind(InjectableInterface::class, InjectableImpl::class);

        $result = $this->resolver->resolve(ClassWithInjectAttr::class);

        self::assertInstanceOf(InjectableImpl::class, $result->dep);
    }

    #[Test]
    public function throwsContainerExceptionForUnresolvablePrimitive(): void
    {
        $this->expectException(ContainerException::class);

        $this->resolver->resolve(ClassWithPrimitiveDep::class);
    }

    #[Test]
    public function throwsContainerExceptionForNonExistentClass(): void
    {
        $this->expectException(ContainerException::class);

        $this->resolver->resolve('NonExistent\Class\Name');
    }

    #[Test]
    public function throwsContainerExceptionForAbstractClass(): void
    {
        $this->expectException(ContainerException::class);

        $this->resolver->resolve(AbstractClassFixture::class);
    }

    #[Test]
    public function resolvesClosureCallable(): void
    {
        $result = $this->resolver->resolveCallable(fn () => 'hello');

        self::assertSame('hello', $result);
    }

    #[Test]
    public function resolvesClosureWithDependencies(): void
    {
        $result = $this->resolver->resolveCallable(fn (NoConstructorClass $c) => $c);

        self::assertInstanceOf(NoConstructorClass::class, $result);
    }
}

// --- Test fixtures ---

final class NoConstructorClass
{
}

interface InjectableInterface
{
}

final class InjectableImpl implements InjectableInterface
{
}

final class ClassWithTypedDep
{
    public function __construct(
        public readonly NoConstructorClass $dep,
    ) {
    }
}

final class ClassWithDefaultParam
{
    public function __construct(
        public readonly string $value = 'default',
    ) {
    }
}

final class ClassWithInjectAttr
{
    public function __construct(
        #[Inject(InjectableInterface::class)]
        public readonly InjectableInterface $dep,
    ) {
    }
}

final class ClassWithPrimitiveDep
{
    public function __construct(
        public readonly string $required,
    ) {
    }
}

abstract class AbstractClassFixture
{
}
