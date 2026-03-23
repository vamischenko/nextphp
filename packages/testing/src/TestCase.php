<?php

declare(strict_types=1);

namespace Nextphp\Testing;

use Nextphp\Routing\Router;
use Nextphp\Http\Kernel\HttpKernel;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<string, mixed> $json
     */
    protected function response(int $status, string $body = '', array $json = []): TestResponse
    {
        return new TestResponse($status, $body, $json);
    }

    /**
     * @param callable(string, string, array<string, mixed>): array{status:int, body?:string, json?:array<string,mixed>} $handler
     */
    protected function client(callable $handler): HttpTestClient
    {
        return new HttpTestClient($handler);
    }

    protected function routingClient(Router $router): RoutingHttpTestClient
    {
        return new RoutingHttpTestClient($router);
    }

    protected function kernelClient(HttpKernel $kernel): KernelHttpTestClient
    {
        return new KernelHttpTestClient($kernel);
    }
}
