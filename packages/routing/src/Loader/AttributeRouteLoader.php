<?php

declare(strict_types=1);

namespace Nextphp\Routing\Loader;

use Nextphp\Routing\Attributes\Route as RouteAttribute;
use Nextphp\Routing\Route;
use Nextphp\Routing\Router;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

final class AttributeRouteLoader
{
    public function __construct(
        private readonly Router $router,
    ) {
    }

    /**
     * Scan a class for #[Route] attributes and register routes.
     *
     * @throws ReflectionException
     */
    public function load(string $class): void
    {
        $reflector = new ReflectionClass($class);

        // Class-level prefix
        $classPrefix = '';
        $classMiddleware = [];

        $classAttrs = $reflector->getAttributes(RouteAttribute::class);

        if ($classAttrs !== []) {
            /** @var RouteAttribute $classRoute */
            $classRoute = $classAttrs[0]->newInstance();
            $classPrefix = rtrim($classRoute->path, '/');
            $classMiddleware = $classRoute->middleware;
        }

        foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(RouteAttribute::class);

            if ($attrs === []) {
                continue;
            }

            /** @var RouteAttribute $attr */
            $attr = $attrs[0]->newInstance();

            $path = $classPrefix . '/' . ltrim($attr->path, '/');
            $path = '/' . ltrim($path, '/');

            $canMiddleware = array_map(static fn (string $ability): string => 'can:' . $ability, $attr->can);
            $middleware = array_merge($classMiddleware, $attr->middleware, $canMiddleware);
            $handler = [$class, $method->getName()];

            $route = new Route(
                methods: $attr->methods,
                path: $path,
                handler: $handler,
                name: $attr->name,
                middleware: $middleware,
            );

            $this->router->addRoute($route);
        }
    }
}
