<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Collector;

/**
 * A collector gathers a specific type of profiling data (queries, timeline, memory, etc.)
 * and returns it as an array for rendering.
 */
interface CollectorInterface
{
    /**
     * Unique name used as panel tab label (e.g. "queries", "timeline").
     */
    public function getName(): string;

    /**
     * Collected data ready for the renderer.
     *
     * @return array<string, mixed>
     */
    public function collect(): array;
}
