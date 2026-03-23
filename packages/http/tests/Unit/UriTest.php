<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use InvalidArgumentException;
use Nextphp\Http\Message\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    #[Test]
    public function parseFullUri(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/path?query=1#frag');

        self::assertSame('https', $uri->getScheme());
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('example.com', $uri->getHost());
        self::assertSame(8080, $uri->getPort());
        self::assertSame('/path', $uri->getPath());
        self::assertSame('query=1', $uri->getQuery());
        self::assertSame('frag', $uri->getFragment());
    }

    #[Test]
    public function emptyUri(): void
    {
        $uri = new Uri();

        self::assertSame('', (string) $uri);
    }

    #[Test]
    public function withScheme(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('https', $uri->withScheme('HTTPS')->getScheme());
    }

    #[Test]
    public function withHost(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('other.com', $uri->withHost('OTHER.COM')->getHost());
    }

    #[Test]
    public function withPort(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame(9000, $uri->withPort(9000)->getPort());
    }

    #[Test]
    public function withPortNullRemovesPort(): void
    {
        $uri = new Uri('http://example.com:8080');

        self::assertNull($uri->withPort(null)->getPort());
    }

    #[Test]
    public function defaultPortIsStrippedFromHttp(): void
    {
        $uri = new Uri('http://example.com:80');

        self::assertNull($uri->getPort());
    }

    #[Test]
    public function defaultPortIsStrippedFromHttps(): void
    {
        $uri = new Uri('https://example.com:443');

        self::assertNull($uri->getPort());
    }

    #[Test]
    public function withPath(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('/new-path', $uri->withPath('/new-path')->getPath());
    }

    #[Test]
    public function withQuery(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('foo=bar', $uri->withQuery('?foo=bar')->getQuery());
    }

    #[Test]
    public function withFragment(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('section', $uri->withFragment('#section')->getFragment());
    }

    #[Test]
    public function withUserInfo(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('user:pass', $uri->withUserInfo('user', 'pass')->getUserInfo());
    }

    #[Test]
    public function withUserInfoNoPassword(): void
    {
        $uri = new Uri('http://example.com');

        self::assertSame('user', $uri->withUserInfo('user')->getUserInfo());
    }

    #[Test]
    public function toStringReconstructsUri(): void
    {
        $original = 'https://example.com/path?foo=bar#section';
        $uri = new Uri($original);

        self::assertSame($original, (string) $uri);
    }

    #[Test]
    public function getAuthorityWithUserInfo(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/path');

        self::assertSame('user:pass@example.com:8080', $uri->getAuthority());
    }

    #[Test]
    public function throwsOnInvalidPort(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Uri())->withPort(99999);
    }

    #[Test]
    public function immutability(): void
    {
        $original = new Uri('http://example.com');
        $modified = $original->withPath('/new');

        self::assertNotSame($original, $modified);
        self::assertSame('', $original->getPath());
        self::assertSame('/new', $modified->getPath());
    }
}
