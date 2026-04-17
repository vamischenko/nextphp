<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Collector;

/**
 * Reports current PHP memory usage.
 *
 * @psalm-api
 */
final class MemoryCollector implements CollectorInterface
{
    /**
      * @psalm-pure
     */
    public function getName(): string
    {
        return 'memory';
    }

    public function collect(): array
    {
        return [
            'current_mb' => round((float) memory_get_usage(true) / 1024.0 / 1024.0, 2),
            'peak_mb'    => round((float) memory_get_peak_usage(true) / 1024.0 / 1024.0, 2),
        ];
    }
}
