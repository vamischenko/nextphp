<?php

declare(strict_types=1);

namespace Nextphp\Routing\RateLimit;

interface RateLimiterInterface
{
    /**
     * Attempt to consume one hit for the given key.
     *
     * @return RateLimitResult Current state after the hit.
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult;

    /**
     * Reset the counter for the given key.
     */
    public function reset(string $key): void;
}
