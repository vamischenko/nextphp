<?php

declare(strict_types=1);

namespace Nextphp\Http\Cookie;

use Psr\Http\Message\ResponseInterface;

/**
 * Collects outgoing cookies and applies them to a PSR-7 response.
 */
final class CookieJar
{
    /** @var list<Cookie> */
    private array $cookies = [];

    /**
      * @psalm-external-mutation-free
     */
    public function set(Cookie $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function queue(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
    ): void {
        $this->set(new Cookie($name, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite));
    }

    public function forget(string $name, string $path = '/', string $domain = ''): void
    {
        $this->set(Cookie::expire($name, $path, $domain));
    }

    /** @return list<Cookie> */
    public function all(): array
    {
        return $this->cookies;
    }

    /**
      * @psalm-mutation-free
     */
    public function isEmpty(): bool
    {
        return $this->cookies === [];
    }

    /**
     * Write all queued cookies as Set-Cookie headers onto the response.
     */
    public function applyToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->cookies as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', $cookie->toHeaderValue());
        }

        return $response;
    }
}
