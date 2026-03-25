<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Cookie\Cookie;
use Nextphp\Http\Cookie\CookieJar;
use Nextphp\Http\Cookie\CookieMiddleware;
use Nextphp\Http\Handler\CallableHandler;
use Nextphp\Http\Message\Response;
use Nextphp\Http\Message\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(Cookie::class)]
#[CoversClass(CookieJar::class)]
#[CoversClass(CookieMiddleware::class)]
final class CookieTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Cookie value object
    // -------------------------------------------------------------------------

    #[Test]
    public function cookieToHeaderValueBasic(): void
    {
        $cookie = new Cookie('session', 'abc123');
        $header = $cookie->toHeaderValue();

        self::assertStringContainsString('session=abc123', $header);
        self::assertStringContainsString('Path=/', $header);
        self::assertStringContainsString('HttpOnly', $header);
        self::assertStringContainsString('SameSite=Lax', $header);
    }

    #[Test]
    public function cookieWithSecureFlag(): void
    {
        $cookie = new Cookie('token', 'xyz', secure: true);
        self::assertStringContainsString('Secure', $cookie->toHeaderValue());
    }

    #[Test]
    public function cookieWithExpires(): void
    {
        $expires = time() + 3600;
        $cookie  = new Cookie('tok', 'val', expires: $expires);
        $header  = $cookie->toHeaderValue();

        self::assertStringContainsString('Expires=', $header);
        self::assertStringContainsString('Max-Age=', $header);
    }

    #[Test]
    public function cookieExpire(): void
    {
        $cookie = Cookie::expire('old');
        self::assertSame('', $cookie->getValue());
        self::assertSame(1, $cookie->getExpires());
    }

    #[Test]
    public function cookieWithValue(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $new    = $cookie->withValue('baz');

        self::assertSame('bar', $cookie->getValue());
        self::assertSame('baz', $new->getValue());
    }

    // -------------------------------------------------------------------------
    // CookieJar
    // -------------------------------------------------------------------------

    #[Test]
    public function cookieJarAppliesSetCookieHeaders(): void
    {
        $jar = new CookieJar();
        $jar->queue('foo', 'bar');
        $jar->queue('baz', 'qux');

        $response = $jar->applyToResponse(new Response(200));

        self::assertCount(2, $response->getHeader('Set-Cookie'));
    }

    #[Test]
    public function cookieJarForgetSetsExpiredCookie(): void
    {
        $jar = new CookieJar();
        $jar->forget('session');

        $response = $jar->applyToResponse(new Response(200));
        $header   = $response->getHeader('Set-Cookie')[0] ?? '';

        self::assertStringContainsString('session=', $header);
        self::assertStringContainsString('Expires=', $header);
    }

    #[Test]
    public function cookieJarIsEmptyInitially(): void
    {
        self::assertTrue((new CookieJar())->isEmpty());
    }

    // -------------------------------------------------------------------------
    // CookieMiddleware
    // -------------------------------------------------------------------------

    #[Test]
    public function middlewareParsesIncomingCookies(): void
    {
        $request = (new ServerRequest('GET', 'http://example.com'))
            ->withHeader('Cookie', 'foo=bar; baz=qux');

        $handler = new CallableHandler(static function (ServerRequestInterface $req): ResponseInterface {
            /** @var array<string, string> $cookies */
            $cookies = $req->getAttribute('cookies', []);
            self::assertSame('bar', $cookies['foo']);
            self::assertSame('qux', $cookies['baz']);
            return new Response(200);
        });

        (new CookieMiddleware())->process($request, $handler);
    }

    #[Test]
    public function middlewareHandlesEmptyCookieHeader(): void
    {
        $request = new ServerRequest('GET', 'http://example.com');
        $handler = new CallableHandler(static function (ServerRequestInterface $req): ResponseInterface {
            self::assertSame([], $req->getAttribute('cookies'));
            return new Response(200);
        });

        (new CookieMiddleware())->process($request, $handler);
    }

    #[Test]
    public function middlewareAppliesJarCookiesToResponse(): void
    {
        $jar = new CookieJar();
        $jar->queue('out', 'value');

        $request = new ServerRequest('GET', 'http://example.com');
        $handler = new CallableHandler(static fn (): ResponseInterface => new Response(200));

        $response = (new CookieMiddleware($jar))->process($request, $handler);

        self::assertNotEmpty($response->getHeader('Set-Cookie'));
        self::assertStringContainsString('out=value', $response->getHeader('Set-Cookie')[0]);
    }
}
