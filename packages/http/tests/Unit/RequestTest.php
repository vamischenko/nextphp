<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Message\Request;
use Nextphp\Http\Message\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    #[Test]
    public function getMethod(): void
    {
        $request = new Request('get', 'http://example.com');

        self::assertSame('GET', $request->getMethod());
    }

    #[Test]
    public function withMethod(): void
    {
        $request = (new Request('GET', 'http://example.com'))->withMethod('POST');

        self::assertSame('POST', $request->getMethod());
    }

    #[Test]
    public function getUri(): void
    {
        $request = new Request('GET', 'http://example.com/path');

        self::assertSame('http://example.com/path', (string) $request->getUri());
    }

    #[Test]
    public function hostHeaderSetFromUri(): void
    {
        $request = new Request('GET', 'http://example.com');

        self::assertSame('example.com', $request->getHeaderLine('Host'));
    }

    #[Test]
    public function getRequestTarget(): void
    {
        $request = new Request('GET', 'http://example.com/path?foo=bar');

        self::assertSame('/path?foo=bar', $request->getRequestTarget());
    }

    #[Test]
    public function withRequestTarget(): void
    {
        $request = (new Request('GET', 'http://example.com'))->withRequestTarget('*');

        self::assertSame('*', $request->getRequestTarget());
    }

    #[Test]
    public function withUri(): void
    {
        $original = new Request('GET', 'http://example.com');
        $modified = $original->withUri(new Uri('http://other.com/new'));

        self::assertSame('other.com', $modified->getHeaderLine('Host'));
    }

    #[Test]
    public function withUriPreserveHost(): void
    {
        $original = new Request('GET', 'http://example.com');
        $modified = $original->withUri(new Uri('http://other.com'), preserveHost: true);

        self::assertSame('example.com', $modified->getHeaderLine('Host'));
    }

    #[Test]
    public function bodyAsString(): void
    {
        $request = new Request('POST', 'http://example.com', [], 'body content');

        self::assertSame('body content', (string) $request->getBody());
    }

    #[Test]
    public function immutability(): void
    {
        $original = new Request('GET', 'http://example.com');
        $modified = $original->withMethod('POST');

        self::assertNotSame($original, $modified);
        self::assertSame('GET', $original->getMethod());
    }
}
