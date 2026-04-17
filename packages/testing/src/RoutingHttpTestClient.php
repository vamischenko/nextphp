<?php

declare(strict_types=1);

namespace Nextphp\Testing;

use Nextphp\Http\Handler\CallableHandler;
use Nextphp\Http\Message\Response;
use Nextphp\Http\Message\ServerRequest;
use Nextphp\Http\Middleware\Pipeline;
use Nextphp\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

final class RoutingHttpTestClient
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly Router $router,
    ) {
    }

    public function withMiddleware(MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;

        return $clone;
    }

    public function get(string $uri): TestResponse
    {
        return $this->request('GET', $uri);
    }

    /**
     * @param array<string, mixed> $parsedBody
     */
    public function post(string $uri, array $parsedBody = []): TestResponse
    {
        return $this->request('POST', $uri, $parsedBody);
    }

    /**
     * @param array<string, mixed> $parsedBody
     */
    public function request(string $method, string $uri, array $parsedBody = []): TestResponse
    {
        $match = $this->router->dispatch($method, $uri);
        $route = $match->route;
        $request = new ServerRequest($method, $uri);
        $request = $request->withParsedBody($parsedBody);
        foreach ($match->params as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $fallback = new CallableHandler(function (ServerRequestInterface $request) use ($route, $match): ResponseInterface {
            /** @var ServerRequest $request */
            $result = $this->invokeRouteHandler($route->getHandler(), $request, $match->params);

            return $this->normalizeResult($result);
        });

        $pipeline = new Pipeline($fallback);
        $pipeline = $pipeline->pipeMany($this->middleware);
        $response = $pipeline->handle($request);

        $body = (string) $response->getBody();
        $json = [];
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }

        return new TestResponse($response->getStatusCode(), $body, $json);
    }

    /**
     * @param array<string, string> $params
     */
    private function invokeRouteHandler(mixed $handler, ServerRequest $request, array $params): mixed
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }

        if (! is_callable($handler)) {
            throw new \RuntimeException('Route handler is not callable.');
        }

        $reflection = is_array($handler)
            ? new \ReflectionMethod($handler[0], (string) $handler[1])
            : new \ReflectionFunction(\Closure::fromCallable($handler));

        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (
                $type instanceof \ReflectionNamedType
                && ($type->getName() === ServerRequest::class || $type->getName() === ServerRequestInterface::class)
            ) {
                $args[] = $request;
                continue;
            }

            $name = $parameter->getName();
            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            }
        }

        return $handler(...$args);
    }

    private function normalizeResult(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        if (is_string($result)) {
            return new Response(200, body: $result);
        }

        return new Response(204);
    }
}
