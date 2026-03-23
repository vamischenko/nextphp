<?php

declare(strict_types=1);

namespace Nextphp\Queue\Backoff;

final class ExponentialBackoffStrategy implements BackoffStrategyInterface
{
    public function __construct(
        private readonly int $baseDelaySeconds = 1,
        private readonly int $maxDelaySeconds = 60,
    ) {
    }

    public function delaySeconds(int $attempt): int
    {
        $value = $this->baseDelaySeconds * (2 ** max($attempt - 1, 0));

        return min($value, $this->maxDelaySeconds);
    }
}
