<?php

declare(strict_types=1);

namespace Nextphp\Routing\RateLimit;

/**
 * In-memory rate limiter.  Useful for testing and single-process workers.
 * For multi-server deployments replace with a Redis- or cache-backed implementation.
 */
final class ArrayRateLimiter implements RateLimiterInterface
{
    /**
     * @var array<string, array{hits: int, resets_at: int}>
     */
    private array $buckets = [];

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        $now = time();

        if (!isset($this->buckets[$key]) || $this->buckets[$key]['resets_at'] <= $now) {
            $this->buckets[$key] = [
                'hits'     => 0,
                'resets_at' => $now + $decaySeconds,
            ];
        }

        $this->buckets[$key]['hits']++;

        $hits      = $this->buckets[$key]['hits'];
        $resetsAt  = $this->buckets[$key]['resets_at'];
        $remaining = $maxAttempts - $hits;

        return new RateLimitResult($remaining, $maxAttempts, $resetsAt);
    }

    public function reset(string $key): void
    {
        unset($this->buckets[$key]);
    }
}
