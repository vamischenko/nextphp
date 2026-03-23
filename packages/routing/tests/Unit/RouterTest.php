<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tests\Unit;

use Nextphp\Routing\Exception\MethodNotAllowedException;
use Nextphp\Routing\Exception\RouteNotFoundException;
use Nextphp\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    #[Test]
    public function getRoute(): void
    {
        $this->router->get('/users', fn () => 'list');

        $result = $this->router->dispatch('GET', '/users');

        self::assertNotNull($result);
    }

    #[Test]
    public function postRoute(): void
    {
        $this->router->post('/users', fn () => 'create');

        $result = $this->router->dispatch('POST', '/users');

        self::assertNotNull($result);
    }

    #[Test]
    public function putPatchDeleteOptions(): void
    {
        $this->router->put('/r', fn () => null);
        $this->router->patch('/r', fn () => null);
        $this->router->delete('/r', fn () => null);
        $this->router->options('/r', fn () => null);

        self::assertNotNull($this->router->dispatch('PUT', '/r'));
        self::assertNotNull($this->router->dispatch('PATCH', '/r'));
        self::assertNotNull($this->router->dispatch('DELETE', '/r'));
        self::assertNotNull($this->router->dispatch('OPTIONS', '/r'));
    }

    #[Test]
    public function anyRoute(): void
    {
        $this->router->any('/any', fn () => null);

        self::assertNotNull($this->router->dispatch('GET', '/any'));
        self::assertNotNull($this->router->dispatch('POST', '/any'));
        self::assertNotNull($this->router->dispatch('DELETE', '/any'));
    }

    #[Test]
    public function routeWithParam(): void
    {
        $this->router->get('/users/{id}', fn () => null);

        $result = $this->router->dispatch('GET', '/users/42');

        self::assertSame(['id' => '42'], $result->params);
    }

    #[Test]
    public function namedRoute(): void
    {
        $this->router->get('/users/{id}', fn () => null)->named('users.show');

        $url = $this->router->getUrlGenerator()->generate('users.show', ['id' => '5']);

        self::assertSame('/users/5', $url);
    }

    #[Test]
    public function throwsRouteNotFoundException(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $this->router->dispatch('GET', '/nonexistent');
    }

    #[Test]
    public function throwsMethodNotAllowedException(): void
    {
        $this->router->get('/users', fn () => null);

        $this->expectException(MethodNotAllowedException::class);

        $this->router->dispatch('DELETE', '/users');
    }

    #[Test]
    public function methodNotAllowedContainsAllowedMethods(): void
    {
        $this->router->get('/users', fn () => null);
        $this->router->post('/users', fn () => null);

        try {
            $this->router->dispatch('DELETE', '/users');
            self::fail('Expected MethodNotAllowedException');
        } catch (MethodNotAllowedException $e) {
            self::assertContains('GET', $e->getAllowedMethods());
            self::assertContains('POST', $e->getAllowedMethods());
        }
    }

    #[Test]
    public function routeGroupPrefix(): void
    {
        $this->router->group('/api/v1', function ($group): void {
            $group->get('/users', fn () => null);
        });

        $result = $this->router->dispatch('GET', '/api/v1/users');

        self::assertNotNull($result);
    }

    #[Test]
    public function resourceRoutes(): void
    {
        $this->router->resource('/posts', 'PostController');

        self::assertNotNull($this->router->dispatch('GET', '/posts'));
        self::assertNotNull($this->router->dispatch('POST', '/posts'));
        self::assertNotNull($this->router->dispatch('GET', '/posts/1'));
        self::assertNotNull($this->router->dispatch('PUT', '/posts/1'));
        self::assertNotNull($this->router->dispatch('PATCH', '/posts/1'));
        self::assertNotNull($this->router->dispatch('DELETE', '/posts/1'));
    }

    #[Test]
    public function headMappedToGet(): void
    {
        $this->router->get('/ping', fn () => null);

        $result = $this->router->dispatch('HEAD', '/ping');

        self::assertNotNull($result);
    }
}
