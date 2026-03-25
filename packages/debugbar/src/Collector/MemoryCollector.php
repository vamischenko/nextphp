<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Collector;

/**
 * Reports current PHP memory usage.
 */
final class MemoryCollector implements CollectorInterface
{
    public function getName(): string
    {
        return 'memory';
    }

    public function collect(): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb'    => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }
}
