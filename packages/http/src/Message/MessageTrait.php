<?php

declare(strict_types=1);

namespace Nextphp\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    private string $protocolVersion = '1.1';

    /** @var array<string, string[]> */
    private array $headers = [];

    /** @var array<string, string> lowercase header name => original header name */
    private array $headerNames = [];

    private StreamInterface $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * @return array<string, string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @return string[]
     */
    public function getHeader(string $name): array
    {
        $lower = strtolower($name);

        if (!isset($this->headerNames[$lower])) {
            return [];
        }

        return $this->headers[$this->headerNames[$lower]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $lower = strtolower($name);
        $values = $this->normalizeHeaderValue($value);

        if (isset($clone->headerNames[$lower])) {
            unset($clone->headers[$clone->headerNames[$lower]]);
        }

        $clone->headerNames[$lower] = $name;
        $clone->headers[$name] = $values;

        return $clone;
    }

    public function withAddedHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $lower = strtolower($name);
        $values = $this->normalizeHeaderValue($value);

        if (isset($clone->headerNames[$lower])) {
            $existing = $clone->headerNames[$lower];
            $clone->headers[$existing] = array_merge($clone->headers[$existing], $values);
        } else {
            $clone->headerNames[$lower] = $name;
            $clone->headers[$name] = $values;
        }

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $lower = strtolower($name);

        if (isset($clone->headerNames[$lower])) {
            unset($clone->headers[$clone->headerNames[$lower]], $clone->headerNames[$lower]);
        }

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @param array<string, string[]>|array<string, string> $headers
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $lower = strtolower($name);
            $values = $this->normalizeHeaderValue($value);
            $this->headerNames[$lower] = $name;
            $this->headers[$name] = $values;
        }
    }

    /**
     * @return string[]
     */
    private function normalizeHeaderValue(mixed $value): array
    {
        if (is_array($value)) {
            if ($value === []) {
                throw new InvalidArgumentException('Header value cannot be an empty array.');
            }

            return array_values(array_map('strval', $value));
        }

        return [(string) $value];
    }
}
