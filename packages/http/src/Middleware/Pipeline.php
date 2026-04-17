<?php

declare(strict_types=1);

namespace Nextphp\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class Pipeline implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    private int $index = 0;

    private ?RequestHandlerInterface $fallback;

    /**
      * @psalm-mutation-free
     */
    public function __construct(?RequestHandlerInterface $fallback = null)
    {
        $this->fallback = $fallback;
    }

    public function pipe(MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;

        return $clone;
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function pipeMany(array $middlewares): self
    {
        $pipeline = $this;

        foreach ($middlewares as $middleware) {
            $pipeline = $pipeline->pipe($middleware);
        }

        return $pipeline;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->middleware[$this->index])) {
            $middleware = $this->middleware[$this->index];
            $next = clone $this;
            $next->index++;

            return $middleware->process($request, $next);
        }

        if ($this->fallback !== null) {
            return $this->fallback->handle($request);
        }

        throw new RuntimeException('Middleware pipeline exhausted with no fallback handler.');
    }
}
