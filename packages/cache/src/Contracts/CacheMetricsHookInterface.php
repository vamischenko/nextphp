<?php

declare(strict_types=1);

namespace Nextphp\Cache\Contracts;

/**
 * @psalm-mutable
 */
interface CacheMetricsHookInterface
{
    /**
     * @psalm-impure
     */
    public function record(string $operation, float $milliseconds): void;
}
