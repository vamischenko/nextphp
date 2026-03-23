<?php

declare(strict_types=1);

namespace Nextphp\Routing;

use Closure;

final class RouteGroup
{
    private string $prefix;

    /** @var string[] */
    private array $middleware;

    private string $namePrefix;

    public function __construct(
        private readonly Router $router,
        string $prefix = '',
        array $middleware = [],
        string $namePrefix = '',
    ) {
        $this->prefix = rtrim($prefix, '/');
        $this->middleware = $middleware;
        $this->namePrefix = $namePrefix;
    }

    public function prefix(string $prefix): self
    {
        return new self($this->router, $this->prefix . '/' . trim($prefix, '/'), $this->middleware, $this->namePrefix);
    }

    /**
     * @param string[] $middleware
     */
    public function middleware(array $middleware): self
    {
        return new self($this->router, $this->prefix, array_merge($this->middleware, $middleware), $this->namePrefix);
    }

    public function name(string $prefix): self
    {
        return new self($this->router, $this->prefix, $this->middleware, $this->namePrefix . $prefix);
    }

    public function group(Closure $callback): void
    {
        $callback($this);
    }

    public function get(string $path, mixed $handler): Route
    {
        return $this->add(['GET', 'HEAD'], $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->add(['POST'], $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->add(['PUT'], $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->add(['PATCH'], $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->add(['DELETE'], $path, $handler);
    }

    /**
     * @param string[] $methods
     */
    private function add(array $methods, string $path, mixed $handler): Route
    {
        $fullPath = $this->prefix . '/' . ltrim($path, '/');
        $fullPath = '/' . ltrim($fullPath, '/');

        $route = new Route($methods, $fullPath, $handler, '', $this->middleware);

        if ($this->namePrefix !== '') {
            $route->withNamePrefix($this->namePrefix);
        }

        $this->router->addRoute($route);

        return $route;
    }
}
