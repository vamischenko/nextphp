<?php

declare(strict_types=1);

namespace Nextphp\Cache\Instrumentation;

use Nextphp\Cache\Contracts\CacheMetricsHookInterface;

final class BenchmarkHook implements CacheMetricsHookInterface
{
    /** @var array<int, array{operation: string, ms: float}> */
    private array $entries = [];

    public function record(string $operation, float $milliseconds): void
    {
        $this->entries[] = ['operation' => $operation, 'ms' => $milliseconds];
    }

    /**
     * @return array<int, array{operation: string, ms: float}>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
