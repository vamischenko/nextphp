<?php

declare(strict_types=1);

namespace Nextphp\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;

    private string $method;

    private string $requestTarget = '';

    private UriInterface $uri;

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $version = '1.1',
    ) {
        $this->method = strtoupper($method);
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->setHeaders($headers);
        $this->protocolVersion = $version;

        if ($body === null) {
            $this->body = Stream::fromString();
        } elseif (is_string($body)) {
            $this->body = Stream::fromString($body);
        } else {
            $this->body = $body;
        }

        // Set Host header from URI if not provided
        if (!$this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $host = $this->uri->getHost();

            if ($this->uri->getPort() !== null) {
                $host .= ':' . $this->uri->getPort();
            }

            $this->headerNames['host'] = 'Host';
            $this->headers['Host'] = [$host];
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $clone;
        }

        if ($uri->getHost() !== '') {
            $host = $uri->getHost();

            if ($uri->getPort() !== null) {
                $host .= ':' . $uri->getPort();
            }

            $clone->headerNames['host'] = 'Host';
            $clone->headers['Host'] = [$host];
        }

        return $clone;
    }
}
