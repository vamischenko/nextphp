<?php

declare(strict_types=1);

namespace Nextphp\Cache\Contracts;

interface CacheMetricsHookInterface
{
    public function record(string $operation, float $milliseconds): void;
}
