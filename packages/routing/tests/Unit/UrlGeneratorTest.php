<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tests\Unit;

use InvalidArgumentException;
use Nextphp\Routing\Router;
use Nextphp\Routing\UrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlGenerator::class)]
final class UrlGeneratorTest extends TestCase
{
    private Router $router;

    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/users', fn () => null)->named('users.index');
        $this->router->get('/users/{id}', fn () => null)->named('users.show');
        $this->router->get('/posts/{slug}/comments/{id}', fn () => null)->named('post.comment');

        $this->generator = $this->router->getUrlGenerator();
    }

    #[Test]
    public function generateStaticUrl(): void
    {
        self::assertSame('/users', $this->generator->generate('users.index'));
    }

    #[Test]
    public function generateUrlWithParam(): void
    {
        self::assertSame('/users/42', $this->generator->generate('users.show', ['id' => '42']));
    }

    #[Test]
    public function generateUrlWithMultipleParams(): void
    {
        $url = $this->generator->generate('post.comment', ['slug' => 'hello-world', 'id' => '5']);

        self::assertSame('/posts/hello-world/comments/5', $url);
    }

    #[Test]
    public function throwsForUnknownRoute(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate('nonexistent');
    }

    #[Test]
    public function throwsForMissingParams(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate('users.show');
    }
}
