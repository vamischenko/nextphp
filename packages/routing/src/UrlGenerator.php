<?php

declare(strict_types=1);

namespace Nextphp\Routing;

use InvalidArgumentException;

final class UrlGenerator
{
    public function __construct(
        private readonly RouteCollection $routes,
    ) {
    }

    /**
     * Generate URL for a named route.
     *
     * @param array<string, string|int> $params
     *
     * @throws InvalidArgumentException
     */
    public function generate(string $name, array $params = []): string
    {
        $route = $this->routes->getByName($name);

        if ($route === null) {
            throw new InvalidArgumentException(sprintf('No route named "%s".', $name));
        }

        $path = $route->getPath();

        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        // Check for unresolved params
        if (preg_match('/\{[^}]+\}/', $path)) {
            throw new InvalidArgumentException(
                sprintf('Missing parameters for route "%s": %s', $name, $path),
            );
        }

        return $path;
    }
}
