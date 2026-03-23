<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Message\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    #[Test]
    public function defaultStatusCode(): void
    {
        $response = new Response();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
    }

    #[Test]
    public function withStatus(): void
    {
        $response = (new Response())->withStatus(404);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not Found', $response->getReasonPhrase());
    }

    #[Test]
    public function withCustomReasonPhrase(): void
    {
        $response = (new Response())->withStatus(200, 'Custom Reason');

        self::assertSame('Custom Reason', $response->getReasonPhrase());
    }

    #[Test]
    public function withHeader(): void
    {
        $response = (new Response())->withHeader('X-Foo', 'bar');

        self::assertTrue($response->hasHeader('x-foo'));
        self::assertSame('bar', $response->getHeaderLine('X-Foo'));
    }

    #[Test]
    public function withBody(): void
    {
        $response = new Response(200, [], 'hello world');

        self::assertSame('hello world', (string) $response->getBody());
    }

    #[Test]
    public function jsonResponse(): void
    {
        $response = Response::json(['key' => 'value']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"key":"value"}', (string) $response->getBody());
    }

    #[Test]
    public function immutability(): void
    {
        $original = new Response(200);
        $modified = $original->withStatus(500);

        self::assertNotSame($original, $modified);
        self::assertSame(200, $original->getStatusCode());
    }

    #[Test]
    public function withAddedHeader(): void
    {
        $response = (new Response())
            ->withHeader('X-Values', 'first')
            ->withAddedHeader('X-Values', 'second');

        self::assertSame(['first', 'second'], $response->getHeader('X-Values'));
    }

    #[Test]
    public function withoutHeader(): void
    {
        $response = (new Response())
            ->withHeader('X-Remove', 'value')
            ->withoutHeader('X-Remove');

        self::assertFalse($response->hasHeader('X-Remove'));
    }

    #[Test]
    public function protocolVersion(): void
    {
        $response = (new Response())->withProtocolVersion('2.0');

        self::assertSame('2.0', $response->getProtocolVersion());
    }
}
