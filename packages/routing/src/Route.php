<?php

declare(strict_types=1);

namespace Nextphp\Routing;

final class Route
{
    /** @var string[] */
    private array $methods;

    private string $path;

    private mixed $handler;

    private string $name = '';

    private string $namePrefix = '';

    /** @var string[] */
    private array $middleware = [];

    private ?RouteCollection $collection = null;

    /**
     * @param string[]             $methods
     * @param string[]             $middleware
     */
    public function __construct(
        array $methods,
        string $path,
        mixed $handler,
        string $name = '',
        array $middleware = [],
    ) {
        $this->methods = array_map('strtoupper', $methods);
        $this->path = $path;
        $this->handler = $handler;
        $this->name = $name;
        $this->middleware = $middleware;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function named(string $name): self
    {
        $this->name = $this->namePrefix !== '' ? $this->namePrefix . $name : $name;

        if ($this->collection !== null) {
            $this->collection->registerName($this->name, $this);
        }

        return $this;
    }

    /**
     * Set a name prefix to be prepended when named() is called.
     * @internal Used by RouteGroup.
     */
    public function withNamePrefix(string $prefix): self
    {
        $this->namePrefix = $prefix;

        return $this;
    }

    /**
     * @param string[] $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Internal: attach collection so named() can register itself.
     * @internal
     */
    public function attachCollection(RouteCollection $collection): void
    {
        $this->collection = $collection;
    }
}
