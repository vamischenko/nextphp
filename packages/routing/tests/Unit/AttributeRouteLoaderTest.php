<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tests\Unit;

use Nextphp\Routing\Attributes\Route;
use Nextphp\Routing\Loader\AttributeRouteLoader;
use Nextphp\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeRouteLoader::class)]
final class AttributeRouteLoaderTest extends TestCase
{
    #[Test]
    public function loadsRoutesFromAttributes(): void
    {
        $router = new Router();
        $loader = new AttributeRouteLoader($router);
        $loader->load(AttributeController::class);

        $result = $router->dispatch('GET', '/users');

        self::assertNotNull($result);
        self::assertSame([AttributeController::class, 'index'], $result->route->getHandler());
    }

    #[Test]
    public function loadsNamedRoute(): void
    {
        $router = new Router();
        $loader = new AttributeRouteLoader($router);
        $loader->load(AttributeController::class);

        $url = $router->getUrlGenerator()->generate('users.show', ['id' => '7']);

        self::assertSame('/users/7', $url);
    }

    #[Test]
    public function loadsWithClassLevelPrefix(): void
    {
        $router = new Router();
        $loader = new AttributeRouteLoader($router);
        $loader->load(PrefixedController::class);

        $result = $router->dispatch('GET', '/api/v1/items');

        self::assertNotNull($result);
    }

    #[Test]
    public function loadsPostRoute(): void
    {
        $router = new Router();
        $loader = new AttributeRouteLoader($router);
        $loader->load(AttributeController::class);

        $result = $router->dispatch('POST', '/users');

        self::assertNotNull($result);
    }

    #[Test]
    public function mapsCanAttributeToMiddlewareAliases(): void
    {
        $router = new Router();
        $loader = new AttributeRouteLoader($router);
        $loader->load(PolicyController::class);

        $result = $router->dispatch('GET', '/reports');
        self::assertSame(['can:view-reports'], $result->route->getMiddleware());
    }
}

// --- Test fixtures ---

final class AttributeController
{
    #[Route('/users', methods: ['GET'])]
    public function index(): string
    {
        return 'list';
    }

    #[Route('/users', methods: ['POST'])]
    public function store(): string
    {
        return 'create';
    }

    #[Route('/users/{id}', methods: ['GET'], name: 'users.show')]
    public function show(int $id): string
    {
        return 'show ' . $id;
    }
}

#[Route('/api/v1')]
final class PrefixedController
{
    #[Route('/items', methods: ['GET'])]
    public function index(): string
    {
        return 'items';
    }
}

final class PolicyController
{
    #[Route('/reports', methods: ['GET'], can: ['view-reports'])]
    public function index(): string
    {
        return 'reports';
    }
}
