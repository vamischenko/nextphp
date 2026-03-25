<?php

declare(strict_types=1);

namespace Nextphp\Routing\RateLimit;

final readonly class RateLimitResult
{
    public function __construct(
        /** Remaining allowed attempts in this window (0 = exhausted). */
        public int $remaining,
        /** Total allowed attempts per window. */
        public int $limit,
        /** Unix timestamp when the current window resets. */
        public int $resetsAt,
    ) {
    }

    public function exceeded(): bool
    {
        return $this->remaining < 0;
    }
}
