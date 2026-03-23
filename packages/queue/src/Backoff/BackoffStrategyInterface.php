<?php

declare(strict_types=1);

namespace Nextphp\Queue\Backoff;

interface BackoffStrategyInterface
{
    public function delaySeconds(int $attempt): int;
}
