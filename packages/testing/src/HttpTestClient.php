<?php

declare(strict_types=1);

namespace Nextphp\Testing;

final class HttpTestClient
{
    /**
     * @param callable(string, string, array<string, mixed>): array{status:int, body?:string, json?:array<string,mixed>} $handler
     */
    private readonly \Closure $handler;

    public function __construct(callable $handler)
    {
        $this->handler = \Closure::fromCallable($handler);
    }

    public function get(string $uri): TestResponse
    {
        return $this->request('GET', $uri);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function post(string $uri, array $payload = []): TestResponse
    {
        return $this->request('POST', $uri, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function request(string $method, string $uri, array $payload = []): TestResponse
    {
        $result = ($this->handler)($method, $uri, $payload);

        return new TestResponse(
            $result['status'],
            $result['body'] ?? '',
            $result['json'] ?? [],
        );
    }
}
