<?php

declare(strict_types=1);

namespace Nextphp\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array<string, mixed> */
    private array $serverParams;

    /** @var array<string, string> */
    private array $cookieParams = [];

    /** @var array<string, string> */
    private array $queryParams = [];

    /** @var UploadedFileInterface[] */
    private array $uploadedFiles = [];

    private mixed $parsedBody = null;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<string, mixed>           $serverParams
     * @param array<string, string|string[]> $headers
     * @param UploadedFileInterface[]        $uploadedFiles
     * @param array<string, string>          $cookieParams
     * @param array<string, string>          $queryParams
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $serverParams = [],
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $version = '1.1',
        array $uploadedFiles = [],
        array $cookieParams = [],
        array $queryParams = [],
        mixed $parsedBody = null,
    ) {
        parent::__construct($method, $uri, $headers, $body, $version);
        $this->serverParams = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;
        $this->parsedBody = $parsedBody;
    }

    /**
     * Create a ServerRequest from PHP superglobals.
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = self::buildUriFromGlobals();
        $headers = getallheaders() ?: [];
        $body = file_get_contents('php://input') ?: '';

        $request = new self(
            method: $method,
            uri: $uri,
            serverParams: $_SERVER,
            headers: $headers,
            body: $body,
            cookieParams: $_COOKIE,
            queryParams: $_GET,
        );

        if (!empty($_POST)) {
            $request = $request->withParsedBody($_POST);
        }

        return $request;
    }

    private static function buildUriFromGlobals(): Uri
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        return new Uri(sprintf('%s://%s%s', $scheme, $host, $requestUri));
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /**
     * @return array<string, string>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * @return UploadedFileInterface[]
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }

    public function withParsedBody(mixed $data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
