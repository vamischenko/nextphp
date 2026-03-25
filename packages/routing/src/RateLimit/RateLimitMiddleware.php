<?php

declare(strict_types=1);

namespace Nextphp\Routing\RateLimit;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 rate-limiting middleware.
 *
 * The limiter key defaults to the client IP address (REMOTE_ADDR server param).
 * A custom key resolver can be provided to key by route, user ID, etc.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    /** @var callable(ServerRequestInterface): string */
    private $keyResolver;

    /**
     * @param callable(ServerRequestInterface): string|null $keyResolver
     *        Returns the bucket key for the request. Defaults to REMOTE_ADDR.
     */
    public function __construct(
        private readonly RateLimiterInterface $limiter,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly int $maxAttempts = 60,
        private readonly int $decaySeconds = 60,
        ?callable $keyResolver = null,
    ) {
        $this->keyResolver = $keyResolver
            ?? static fn (ServerRequestInterface $r): string =>
                (string) ($r->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key    = ($this->keyResolver)($request);
        $result = $this->limiter->hit($key, $this->maxAttempts, $this->decaySeconds);

        if ($result->exceeded()) {
            $retryAfter = max(0, $result->resetsAt - time());

            return $this->responseFactory->createResponse(429)
                ->withHeader('X-RateLimit-Limit', (string) $result->limit)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('Retry-After', (string) $retryAfter)
                ->withHeader('Content-Type', 'application/json');
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $result->limit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $result->remaining));
    }
}
