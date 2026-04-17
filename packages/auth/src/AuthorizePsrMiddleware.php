<?php

declare(strict_types=1);

namespace Nextphp\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthorizePsrMiddleware implements MiddlewareInterface
{
    /**
     * @param callable(ServerRequestInterface): array<int, mixed> $argumentsResolver
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly PolicyRegistry $policies,
        private readonly string $ability,
        private readonly \Closure $argumentsResolver,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $args = ($this->argumentsResolver)($request);
        if (! $this->policies->allows($this->ability, ...$args)) {
            throw new AuthorizationException(sprintf('Not authorized for ability "%s".', $this->ability));
        }

        return $handler->handle($request);
    }
}
