<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

use Closure;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Register a transient binding (new instance on each make).
     */
    public function bind(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a singleton binding (one instance per container lifecycle).
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a scoped binding (one instance per request scope).
     */
    public function scoped(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a pre-built instance.
     */
    public function instance(string $abstract, object $instance): void;

    /**
     * Resolve an abstract from the container with optional parameters.
     *
     * @param array<string, mixed> $parameters
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call a callable with dependency injection.
     *
     * @param callable|array<int, mixed>|string $callback
     * @param array<string, mixed> $parameters
     */
    public function call(callable|array|string $callback, array $parameters = []): mixed;

    /**
     * Check if an abstract is bound in the container.
     */
    public function bound(string $abstract): bool;

    /**
     * Register a service provider.
     */
    public function addServiceProvider(ServiceProviderInterface $provider): void;

    /**
     * Boot all registered service providers.
     */
    public function boot(): void;

    /**
     * Flush all scoped instances (call at the end of request scope).
     */
    public function flushScoped(): void;
}
