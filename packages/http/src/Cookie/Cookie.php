<?php

declare(strict_types=1);

namespace Nextphp\Http\Cookie;

/**
 * Immutable cookie value object.
 */
final class Cookie
{
    public function __construct(
        private readonly string $name,
        private readonly string $value,
        private readonly int $expires = 0,
        private readonly string $path = '/',
        private readonly string $domain = '',
        private readonly bool $secure = false,
        private readonly bool $httpOnly = true,
        private readonly string $sameSite = 'Lax',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Return a copy with a different value.
     */
    public function withValue(string $value): self
    {
        $clone        = clone $this;
        // readonly workaround via clone constructor
        return new self(
            $clone->name,
            $value,
            $clone->expires,
            $clone->path,
            $clone->domain,
            $clone->secure,
            $clone->httpOnly,
            $clone->sameSite,
        );
    }

    /**
     * Render as a Set-Cookie header value.
     */
    public function toHeaderValue(): string
    {
        $parts = [urlencode($this->name) . '=' . urlencode($this->value)];

        if ($this->expires !== 0) {
            $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $this->expires);
            $parts[] = 'Max-Age=' . max(0, $this->expires - time());
        }

        if ($this->path !== '') {
            $parts[] = 'Path=' . $this->path;
        }

        if ($this->domain !== '') {
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($this->sameSite !== '') {
            $parts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $parts);
    }

    /**
     * Create an expired "delete" cookie.
     */
    public static function expire(string $name, string $path = '/', string $domain = ''): self
    {
        return new self($name, '', expires: 1, path: $path, domain: $domain);
    }
}
