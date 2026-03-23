<?php

declare(strict_types=1);

namespace Nextphp\Routing;

final class RouteCollection
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> name => Route */
    private array $namedRoutes = [];

    public function add(Route $route): void
    {
        $this->routes[] = $route;
        $route->attachCollection($this);

        if ($route->getName() !== '') {
            $this->namedRoutes[$route->getName()] = $route;
        }
    }

    public function registerName(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * @return Route[]
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }
}
