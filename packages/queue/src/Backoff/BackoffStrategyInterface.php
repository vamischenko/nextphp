<?php

declare(strict_types=1);

namespace Nextphp\Queue\Backoff;

/**
 * @psalm-mutable
 */
interface BackoffStrategyInterface
{
    /**
     * @psalm-impure
     */
    public function delaySeconds(int $attempt): int;
}
