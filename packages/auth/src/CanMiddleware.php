<?php

declare(strict_types=1);

namespace Nextphp\Auth;

use Nextphp\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CanMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly PolicyRegistry $policies,
        private readonly string $ability,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $subject = $request->getAttribute('user') ?? $request->getAttribute('role') ?? null;
        if (! $this->policies->allows($this->ability, $subject)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        return $handler->handle($request);
    }
}
