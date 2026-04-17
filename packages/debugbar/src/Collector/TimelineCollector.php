<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Collector;

/**
 * Measures named time spans (request start → end, boot, etc.).
 *
 * Usage:
 *   $timeline->start('boot');
 *   // ... do work ...
 *   $timeline->stop('boot');
 */
final class TimelineCollector implements CollectorInterface
{
    /** @var array<string, array{start: float, end: float|null, label: string}> */
    private array $measures = [];

    private readonly float $startedAt;

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    public function start(string $name, ?string $label = null): void
    {
        $this->measures[$name] = [
            'start' => microtime(true),
            'end'   => null,
            'label' => $label ?? $name,
        ];
    }

    public function stop(string $name): void
    {
        if (isset($this->measures[$name])) {
            $this->measures[$name]['end'] = microtime(true);
        }
    }

    /**
      * @psalm-pure
     */
    public function getName(): string
    {
        return 'timeline';
    }

    public function collect(): array
    {
        $now     = microtime(true);
        $entries = [];

        foreach ($this->measures as $key => $m) {
            $end      = $m['end'] ?? $now;
            $entries[] = [
                'label'    => $m['label'],
                'start_ms' => round(($m['start'] - $this->startedAt) * 1000, 2),
                'duration_ms' => round(($end - $m['start']) * 1000, 2),
            ];
        }

        return [
            'total_ms' => round(($now - $this->startedAt) * 1000, 2),
            'entries'  => $entries,
        ];
    }
}
