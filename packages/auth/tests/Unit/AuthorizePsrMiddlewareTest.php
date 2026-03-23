<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\AuthorizationException;
use Nextphp\Auth\AuthorizePsrMiddleware;
use Nextphp\Auth\PolicyRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(AuthorizePsrMiddleware::class)]
final class AuthorizePsrMiddlewareTest extends TestCase
{
    #[Test]
    public function processPassesToHandlerWhenAllowed(): void
    {
        $policies = new PolicyRegistry();
        $policies->define('view', static fn (string $role): bool => $role === 'admin');
        $middleware = new AuthorizePsrMiddleware(
            $policies,
            'view',
            static fn (ServerRequestInterface $request): array => [$request->getAttribute('role', 'guest')],
        );

        $response = $middleware->process(new DummyRequest(['role' => 'admin']), new DummyHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function processThrowsWhenDenied(): void
    {
        $policies = new PolicyRegistry();
        $policies->define('view', static fn (string $role): bool => $role === 'admin');
        $middleware = new AuthorizePsrMiddleware(
            $policies,
            'view',
            static fn (ServerRequestInterface $request): array => [$request->getAttribute('role', 'guest')],
        );

        $this->expectException(AuthorizationException::class);
        $middleware->process(new DummyRequest(['role' => 'user']), new DummyHandler());
    }
}

final class DummyHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new class () implements ResponseInterface {
            public function getProtocolVersion(): string { return '1.1'; }
            public function withProtocolVersion($version): static { return $this; }
            public function getHeaders(): array { return []; }
            public function hasHeader($name): bool { return false; }
            public function getHeader($name): array { return []; }
            public function getHeaderLine($name): string { return ''; }
            public function withHeader($name, $value): static { return $this; }
            public function withAddedHeader($name, $value): static { return $this; }
            public function withoutHeader($name): static { return $this; }
            public function getBody(): \Psr\Http\Message\StreamInterface { throw new \RuntimeException('not used'); }
            public function withBody(\Psr\Http\Message\StreamInterface $body): static { return $this; }
            public function getStatusCode(): int { return 200; }
            public function withStatus($code, $reasonPhrase = ''): static { return $this; }
            public function getReasonPhrase(): string { return 'OK'; }
        };
    }
}

final class DummyRequest implements ServerRequestInterface
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private array $attributes = [])
    {
    }
    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion($version): static { return $this; }
    public function getHeaders(): array { return []; }
    public function hasHeader($name): bool { return false; }
    public function getHeader($name): array { return []; }
    public function getHeaderLine($name): string { return ''; }
    public function withHeader($name, $value): static { return $this; }
    public function withAddedHeader($name, $value): static { return $this; }
    public function withoutHeader($name): static { return $this; }
    public function getBody(): \Psr\Http\Message\StreamInterface { throw new \RuntimeException('not used'); }
    public function withBody(\Psr\Http\Message\StreamInterface $body): static { return $this; }
    public function getRequestTarget(): string { return '/'; }
    public function withRequestTarget($requestTarget): static { return $this; }
    public function getMethod(): string { return 'GET'; }
    public function withMethod($method): static { return $this; }
    public function getUri(): \Psr\Http\Message\UriInterface { throw new \RuntimeException('not used'); }
    public function withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false): static { return $this; }
    public function getServerParams(): array { return []; }
    public function getCookieParams(): array { return []; }
    public function withCookieParams(array $cookies): static { return $this; }
    public function getQueryParams(): array { return []; }
    public function withQueryParams(array $query): static { return $this; }
    public function getUploadedFiles(): array { return []; }
    public function withUploadedFiles(array $uploadedFiles): static { return $this; }
    public function getParsedBody(): mixed { return null; }
    public function withParsedBody($data): static { return $this; }
    public function getAttributes(): array { return $this->attributes; }
    public function getAttribute($name, $default = null): mixed { return $this->attributes[$name] ?? $default; }
    public function withAttribute($name, $value): static { $clone = clone $this; $clone->attributes[$name] = $value; return $clone; }
    public function withoutAttribute($name): static { $clone = clone $this; unset($clone->attributes[$name]); return $clone; }
}
