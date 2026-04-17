<?php

declare(strict_types=1);

namespace Nextphp\Core\Container\Resolver;

use Closure;
use Nextphp\Core\Attributes\Inject;
use Nextphp\Core\Container\ContainerInterface;
use Nextphp\Core\Exception\ContainerException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class ReflectionResolver
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classCache = [];

    /** @var array<string, ReflectionMethod> */
    private static array $methodCache = [];

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
      * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$classCache = [];
        self::$methodCache = [];
    }

    /**
     * Resolve a concrete class by instantiating it with autowired dependencies.
     *
     * @param array<string, mixed> $parameters
     *
     * @throws ContainerException
     */
    public function resolve(string $concrete, array $parameters = []): object
    {
        $reflector = $this->reflectClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException(
                sprintf('Class "%s" is not instantiable (abstract class or interface).', $concrete),
            );
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $resolved = $this->resolveParameters($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($resolved);
    }

    /**
     * Resolve a callable with dependency injection.
     *
     * @param array<string, mixed> $parameters
     *
     * @throws ContainerException
     */
    public function resolveCallable(callable $callback, array $parameters = []): mixed
    {
        if ($callback instanceof Closure) {
            try {
                $reflector = new ReflectionFunction($callback);
            } catch (ReflectionException $e) {
                throw new ContainerException('Cannot reflect closure: ' . $e->getMessage(), 0, $e);
            }

            $resolved = $this->resolveParameters($reflector->getParameters(), $parameters);

            return $callback(...$resolved);
        }

        if (is_array($callback) && count($callback) === 2) {
            [$object, $method] = $callback;

            $reflector = $this->reflectMethod($object, (string) $method);
            $resolved = $this->resolveParameters($reflector->getParameters(), $parameters);

            return $reflector->invoke(is_object($object) ? $object : null, ...$resolved);
        }

        if (is_string($callback) && str_contains($callback, '::')) {
            [$class, $method] = explode('::', $callback, 2);

            $reflector = $this->reflectMethod($class, $method);
            $resolved = $this->resolveParameters($reflector->getParameters(), $parameters);

            return $reflector->invoke(null, ...$resolved);
        }

        if (is_string($callback) && function_exists($callback)) {
            try {
                $reflector = new ReflectionFunction($callback);
            } catch (ReflectionException $e) {
                throw new ContainerException('Cannot reflect function: ' . $e->getMessage(), 0, $e);
            }

            $resolved = $this->resolveParameters($reflector->getParameters(), $parameters);

            return $callback(...$resolved);
        }

        throw new ContainerException('Unsupported callable type.');
    }

    /**
     * @template T of object
     * @param class-string<T> $concrete
     * @return ReflectionClass<T>
     * @throws ContainerException
       * @psalm-external-mutation-free
     */
    private function reflectClass(string $concrete): ReflectionClass
    {
        if (!isset(self::$classCache[$concrete])) {
            try {
                self::$classCache[$concrete] = new ReflectionClass($concrete);
            } catch (ReflectionException $e) {
                throw new ContainerException(
                    sprintf('Cannot reflect class "%s": %s', $concrete, $e->getMessage()),
                    0,
                    $e,
                );
            }
        }

        /** @var ReflectionClass<T> */
        return self::$classCache[$concrete];
    }

    /**
     * @param object|class-string $objectOrClass
     * @throws ContainerException
     */
    private function reflectMethod(object|string $objectOrClass, string $method): ReflectionMethod
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;
        $cacheKey = $class . '::' . $method;

        if (!isset(self::$methodCache[$cacheKey])) {
            try {
                self::$methodCache[$cacheKey] = new ReflectionMethod($objectOrClass, $method);
            } catch (ReflectionException $e) {
                throw new ContainerException('Cannot reflect method: ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$methodCache[$cacheKey];
    }

    /**
     * @param ReflectionParameter[] $params
     * @param array<string, mixed>  $overrides
     *
     * @return array<int, mixed>
     *
     * @throws ContainerException
     */
    private function resolveParameters(array $params, array $overrides): array
    {
        $resolved = [];

        foreach ($params as $param) {
            $resolved[] = $this->resolveParameter($param, $overrides);
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @throws ContainerException
     */
    private function resolveParameter(ReflectionParameter $param, array $overrides): mixed
    {
        $name = $param->getName();

        // 1. Explicit parameter override takes priority
        if (array_key_exists($name, $overrides)) {
            return $overrides[$name];
        }

        // 2. Check for #[Inject] attribute on the parameter
        $injectAttr = $param->getAttributes(Inject::class);

        if ($injectAttr !== []) {
            /** @var Inject $inject */
            $inject = $injectAttr[0]->newInstance();

            return $this->container->make($inject->abstract);
        }

        // 3. Resolve by type hint (class or interface)
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            if ($type->allowsNull() && !$this->container->has($typeName)) {
                return null;
            }

            return $this->container->make($typeName);
        }

        // 4. Use default value if available
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        // 5. Nullable primitive without default -> null
        if ($type instanceof ReflectionNamedType && $type->allowsNull()) {
            return null;
        }

        throw new ContainerException(
            sprintf(
                'Cannot resolve parameter "$%s" of type "%s" — no binding, no default value.',
                $name,
                $type !== null ? (string) $type : 'unknown',
            ),
        );
    }
}
