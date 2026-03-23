<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tests\Unit;

use Nextphp\Routing\Route;
use Nextphp\Routing\Tree\RadixTree;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RadixTree::class)]
final class RadixTreeTest extends TestCase
{
    private RadixTree $tree;

    protected function setUp(): void
    {
        $this->tree = new RadixTree();
    }

    private function route(string $path): Route
    {
        return new Route(['GET'], $path, fn () => null);
    }

    #[Test]
    public function matchStaticRoute(): void
    {
        $route = $this->route('/users');
        $this->tree->insert('GET', '/users', $route);

        $result = $this->tree->search('GET', '/users');

        self::assertNotNull($result);
        self::assertSame($route, $result->route);
    }

    #[Test]
    public function matchRootRoute(): void
    {
        $route = $this->route('/');
        $this->tree->insert('GET', '/', $route);

        $result = $this->tree->search('GET', '/');

        self::assertNotNull($result);
    }

    #[Test]
    public function matchParametricRoute(): void
    {
        $route = $this->route('/users/{id}');
        $this->tree->insert('GET', '/users/{id}', $route);

        $result = $this->tree->search('GET', '/users/42');

        self::assertNotNull($result);
        self::assertSame(['id' => '42'], $result->params);
    }

    #[Test]
    public function matchMultipleParams(): void
    {
        $route = $this->route('/users/{userId}/posts/{postId}');
        $this->tree->insert('GET', '/users/{userId}/posts/{postId}', $route);

        $result = $this->tree->search('GET', '/users/1/posts/99');

        self::assertNotNull($result);
        self::assertSame(['userId' => '1', 'postId' => '99'], $result->params);
    }

    #[Test]
    public function staticRouteHasPriorityOverParam(): void
    {
        $staticRoute = $this->route('/users/me');
        $paramRoute = $this->route('/users/{id}');

        $this->tree->insert('GET', '/users/{id}', $paramRoute);
        $this->tree->insert('GET', '/users/me', $staticRoute);

        $result = $this->tree->search('GET', '/users/me');

        self::assertNotNull($result);
        self::assertSame($staticRoute, $result->route);
    }

    #[Test]
    public function returnsNullForNotFound(): void
    {
        $this->tree->insert('GET', '/users', $this->route('/users'));

        self::assertNull($this->tree->search('GET', '/posts'));
    }

    #[Test]
    public function returnsNullForWrongMethod(): void
    {
        $this->tree->insert('GET', '/users', $this->route('/users'));

        self::assertNull($this->tree->search('POST', '/users'));
    }

    #[Test]
    public function allowedMethodsFor405(): void
    {
        $this->tree->insert('GET', '/users', $this->route('/users'));
        $this->tree->insert('POST', '/users', new Route(['POST'], '/users', fn () => null));

        $allowed = $this->tree->allowedMethods('/users');

        self::assertContains('GET', $allowed);
        self::assertContains('POST', $allowed);
    }

    #[Test]
    public function allowedMethodsEmptyForNotFound(): void
    {
        self::assertSame([], $this->tree->allowedMethods('/nonexistent'));
    }

    #[Test]
    public function matchDeepNestedRoute(): void
    {
        $route = $this->route('/a/b/c/d');
        $this->tree->insert('GET', '/a/b/c/d', $route);

        $result = $this->tree->search('GET', '/a/b/c/d');

        self::assertNotNull($result);
        self::assertSame($route, $result->route);
    }
}
