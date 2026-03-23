<?php

declare(strict_types=1);

namespace Nextphp\Http\Kernel;

use Nextphp\Http\Exception\ExceptionHandler;
use Nextphp\Http\Handler\CallableHandler;
use Nextphp\Http\Message\Response;
use Nextphp\Http\Middleware\MiddlewareAliasRegistry;
use Nextphp\Http\Middleware\Pipeline;
use Nextphp\Routing\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Throwable;

final class HttpKernel
{
    /** @var MiddlewareInterface[] */
    private array $globalMiddleware = [];

    public function __construct(
        private readonly Router $router,
        private readonly ?ContainerInterface $container = null,
        private readonly MiddlewareAliasRegistry $aliases = new MiddlewareAliasRegistry(),
        private readonly ExceptionHandler $exceptions = new ExceptionHandler(),
    ) {
    }

    public function addGlobalMiddleware(MiddlewareInterface $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function aliases(): MiddlewareAliasRegistry
    {
        return $this->aliases;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $path = $request->getUri()->getPath();
            $match = $this->router->dispatch($request->getMethod(), $path);

            foreach ($match->params as $key => $value) {
                $request = $request->withAttribute($key, $value);
            }

            $routeMiddleware = [];
            foreach ($match->route->getMiddleware() as $alias) {
                $routeMiddleware[] = $this->aliases->resolve($alias);
            }

            $fallback = new CallableHandler(function (ServerRequestInterface $request) use ($match): ResponseInterface {
                $handler = $match->route->getHandler();

                if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
                    $controller = $this->container !== null
                        ? $this->container->get($handler[0])
                        : new $handler[0]();
                    $handler = [$controller, $handler[1]];
                }

                if (!is_callable($handler)) {
                    throw new RuntimeException('Route handler is not callable.');
                }

                $arguments = $this->resolveHandlerArguments($handler, $request, $match->params);
                $result = $handler(...$arguments);

                if ($result instanceof ResponseInterface) {
                    return $result;
                }

                if (is_array($result)) {
                    return Response::json($result);
                }

                return new Response(200, body: (string) $result);
            });

            $pipeline = new Pipeline($fallback);
            $pipeline = $pipeline->pipeMany($this->globalMiddleware);
            $pipeline = $pipeline->pipeMany($routeMiddleware);

            return $pipeline->handle($request);
        } catch (Throwable $e) {
            return $this->exceptions->handle($e, $request);
        }
    }

    /**
     * @param array<string, string> $routeParams
     * @return array<int, mixed>
     */
    private function resolveHandlerArguments(callable $handler, ServerRequestInterface $request, array $routeParams): array
    {
        if (is_array($handler)) {
            $reflection = new ReflectionMethod($handler[0], $handler[1]);
        } elseif (is_string($handler) && str_contains($handler, '::')) {
            [$class, $method] = explode('::', $handler, 2);
            $reflection = new ReflectionMethod($class, $method);
        } else {
            $reflection = new ReflectionFunction($handler);
        }
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $name = $type->getName();
                if ($name === ServerRequestInterface::class || $name === PsrServerRequestInterface::class || is_a($request, $name)) {
                    $arguments[] = $request;

                    continue;
                }
            }

            $paramName = $parameter->getName();
            if (array_key_exists($paramName, $routeParams)) {
                $arguments[] = $routeParams[$paramName];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->container !== null) {
                $typeName = $type->getName();
                if ($this->container->has($typeName)) {
                    $arguments[] = $this->container->get($typeName);

                    continue;
                }
            }

            throw new RuntimeException(sprintf('Cannot resolve route argument "%s".', $paramName));
        }

        return $arguments;
    }
}
