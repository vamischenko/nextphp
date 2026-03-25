<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tests\Unit;

use Nextphp\Routing\RateLimit\ArrayRateLimiter;
use Nextphp\Routing\RateLimit\RateLimitMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(RateLimitMiddleware::class)]
#[CoversClass(ArrayRateLimiter::class)]
final class RateLimitMiddlewareTest extends TestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeRequest(string $ip = '1.2.3.4'): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => $ip]);

        return $request;
    }

    private function makeHandler(int $status = 200): RequestHandlerInterface
    {
        $response = $this->makeResponse($status);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }

    private function makeResponse(int $status = 200): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('withHeader')->willReturnSelf();
        $stream = $this->createMock(StreamInterface::class);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }

    private function makeFactory(int $status = 429): ResponseFactoryInterface
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $response = $this->makeResponse($status);
        $factory->method('createResponse')->willReturn($response);

        return $factory;
    }

    private function makeMiddleware(int $max = 3, int $decay = 60): RateLimitMiddleware
    {
        return new RateLimitMiddleware(
            new ArrayRateLimiter(),
            $this->makeFactory(),
            $max,
            $decay,
        );
    }

    // ------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------

    #[Test]
    public function allowsRequestsWithinLimit(): void
    {
        $mw = $this->makeMiddleware(max: 5);

        $response = $mw->process($this->makeRequest(), $this->makeHandler(200));

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function setsRateLimitHeaders(): void
    {
        $limiter = new ArrayRateLimiter();
        $factory = $this->makeFactory();
        $expected = [];

        $inner = $this->createMock(ResponseInterface::class);
        $inner->method('getStatusCode')->willReturn(200);
        $inner->method('withHeader')->willReturnCallback(
            function (string $name, string $value) use ($inner, &$expected): ResponseInterface {
                $expected[$name] = $value;

                return $inner;
            }
        );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($inner);

        $mw = new RateLimitMiddleware($limiter, $factory, maxAttempts: 10, decaySeconds: 60);
        $mw->process($this->makeRequest(), $handler);

        self::assertArrayHasKey('X-RateLimit-Limit', $expected);
        self::assertArrayHasKey('X-RateLimit-Remaining', $expected);
        self::assertSame('10', $expected['X-RateLimit-Limit']);
        self::assertSame('9', $expected['X-RateLimit-Remaining']);
    }

    #[Test]
    public function returns429WhenLimitExceeded(): void
    {
        $limiter = new ArrayRateLimiter();
        $factory = $this->createMock(ResponseFactoryInterface::class);

        $tooManyResponse = $this->createMock(ResponseInterface::class);
        $tooManyResponse->method('getStatusCode')->willReturn(429);
        $tooManyResponse->method('withHeader')->willReturnSelf();

        $factory->method('createResponse')->with(429)->willReturn($tooManyResponse);

        $mw = new RateLimitMiddleware($limiter, $factory, maxAttempts: 2, decaySeconds: 60);
        $request = $this->makeRequest();
        $handler = $this->makeHandler();

        // First two requests succeed
        $mw->process($request, $handler);
        $mw->process($request, $handler);

        // Third request exceeds limit
        $response = $mw->process($request, $handler);

        self::assertSame(429, $response->getStatusCode());
    }

    #[Test]
    public function differentIpsHaveSeparateBuckets(): void
    {
        $mw = $this->makeMiddleware(max: 1);

        $r1 = $mw->process($this->makeRequest('1.1.1.1'), $this->makeHandler());
        $r2 = $mw->process($this->makeRequest('2.2.2.2'), $this->makeHandler());

        self::assertSame(200, $r1->getStatusCode());
        self::assertSame(200, $r2->getStatusCode());
    }

    #[Test]
    public function customKeyResolverIsUsed(): void
    {
        $limiter = new ArrayRateLimiter();
        $factory = $this->makeFactory();
        $mw = new RateLimitMiddleware(
            $limiter,
            $factory,
            maxAttempts: 1,
            decaySeconds: 60,
            keyResolver: static fn (): string => 'fixed-key',
        );

        $mw->process($this->makeRequest('1.1.1.1'), $this->makeHandler());
        $response = $mw->process($this->makeRequest('9.9.9.9'), $this->makeHandler());

        // Both requests share 'fixed-key' bucket, so second exceeds limit
        self::assertSame(429, $response->getStatusCode());
    }

    #[Test]
    public function arrayRateLimiterResetsCounter(): void
    {
        $limiter = new ArrayRateLimiter();
        // Exhaust the 1-request limit
        $limiter->hit('key', 1, 60);
        $result1 = $limiter->hit('key', 1, 60);
        self::assertTrue($result1->exceeded());

        $limiter->reset('key');

        $result2 = $limiter->hit('key', 1, 60);
        self::assertFalse($result2->exceeded());
    }
}
