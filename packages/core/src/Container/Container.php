<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

use Closure;
use Nextphp\Core\Container\Resolver\ReflectionResolver;
use Nextphp\Core\Exception\ContainerException;
use Nextphp\Core\Exception\NotFoundException;

class Container implements ContainerInterface
{
    /** @var array<string, Binding> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $singletons = [];

    /** @var array<string, object> */
    private array $scopedInstances = [];

    /** @var ServiceProviderInterface[] */
    private array $providers = [];

    private bool $booted = false;

    private ReflectionResolver $resolver;

    public function __construct()
    {
        $this->resolver = new ReflectionResolver($this);

        // Bind container itself
        $this->instance(ContainerInterface::class, $this);
        $this->instance(static::class, $this);
    }

    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = new Binding(
            BindingType::Transient,
            $concrete ?? $abstract,
        );
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = new Binding(
            BindingType::Singleton,
            $concrete ?? $abstract,
        );
        // Invalidate any cached singleton when re-registered
        unset($this->singletons[$abstract]);
    }

    public function scoped(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = new Binding(
            BindingType::Scoped,
            $concrete ?? $abstract,
        );
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->bindings[$abstract] = new Binding(
            BindingType::Instance,
            null,
            $instance,
        );
        $this->singletons[$abstract] = $instance;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        // Already resolved singleton / instance
        if (isset($this->singletons[$abstract]) && $parameters === []) {
            return $this->singletons[$abstract];
        }

        // Already resolved scoped instance
        if (isset($this->scopedInstances[$abstract]) && $parameters === []) {
            return $this->scopedInstances[$abstract];
        }

        $binding = $this->bindings[$abstract] ?? null;

        if ($binding === null) {
            // Attempt autowiring for unregistered concrete classes
            return $this->build($abstract, $parameters);
        }

        $instance = $this->resolveBinding($binding, $abstract, $parameters);

        if ($binding->type === BindingType::Singleton && $parameters === []) {
            $this->singletons[$abstract] = $instance;
        }

        if ($binding->type === BindingType::Scoped && $parameters === []) {
            $this->scopedInstances[$abstract] = $instance;
        }

        return $instance;
    }

    public function call(callable|array|string $callback, array $parameters = []): mixed
    {
        return $this->resolver->resolveCallable(
            $this->wrapCallable($callback),
            $parameters,
        );
    }

    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            // Attempt to autowire before throwing
            try {
                return $this->make($id);
            } catch (ContainerException $e) {
                throw new NotFoundException(
                    sprintf('No binding found for "%s".', $id),
                    0,
                    $e,
                );
            }
        }

        return $this->make($id);
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    public function addServiceProvider(ServiceProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            $provider->register($this);
        }

        foreach ($this->providers as $provider) {
            $provider->boot($this);
        }

        $this->booted = true;
    }

    public function flushScoped(): void
    {
        $this->scopedInstances = [];
    }

    /**
     * Build a class that has no binding by autowiring.
     *
     * @param array<string, mixed> $parameters
     *
     * @throws ContainerException
     */
    private function build(string $concrete, array $parameters): object
    {
        return $this->resolver->resolve($concrete, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws ContainerException
     */
    private function resolveBinding(Binding $binding, string $abstract, array $parameters): object
    {
        if ($binding->type === BindingType::Instance) {
            /** @var object $instance */
            $instance = $binding->instance;

            return $instance;
        }

        $concrete = $binding->concrete;

        if ($concrete instanceof Closure) {
            /** @var object $result */
            $result = $concrete($this, $parameters);

            return $result;
        }

        if (is_string($concrete)) {
            if ($concrete === $abstract) {
                return $this->build($concrete, $parameters);
            }

            // Recurse in case the concrete is itself bound (e.g. interface -> class -> singleton)
            return $this->make($concrete, $parameters);
        }

        throw new ContainerException(
            sprintf('Cannot resolve binding for "%s".', $abstract),
        );
    }

    /**
     * Wrap various callable forms to an actual callable.
     *
     * @param callable|array<int, mixed>|string $callback
     */
    private function wrapCallable(callable|array|string $callback): callable
    {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback, 2);
            $object = $this->make($class);

            return [$object, $method];
        }

        /** @var callable $callback */
        return $callback;
    }
}
