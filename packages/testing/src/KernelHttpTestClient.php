<?php

declare(strict_types=1);

namespace Nextphp\Testing;

use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Http\Message\ServerRequest;

final class KernelHttpTestClient
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly HttpKernel $kernel,
    ) {
    }

    public function get(string $uri): TestResponse
    {
        return $this->request('GET', $uri);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function post(string $uri, array $body = []): TestResponse
    {
        return $this->request('POST', $uri, $body);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function request(string $method, string $uri, array $body = []): TestResponse
    {
        $request = (new ServerRequest($method, $uri))->withParsedBody($body);
        $response = $this->kernel->handle($request);
        $rawBody = (string) $response->getBody();
        $decoded = json_decode($rawBody, true);

        return new TestResponse(
            status: $response->getStatusCode(),
            body: $rawBody,
            json: is_array($decoded) ? $decoded : [],
        );
    }
}
