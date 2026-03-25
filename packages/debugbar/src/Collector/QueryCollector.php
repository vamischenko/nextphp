<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Collector;

/**
 * Collects SQL queries manually registered via addQuery().
 *
 * Example integration with ORM:
 *   $collector->addQuery($sql, $bindings, $durationMs);
 */
final class QueryCollector implements CollectorInterface
{
    /** @var array<int, array{sql: string, bindings: mixed[], duration_ms: float}> */
    private array $queries = [];

    /**
     * @param mixed[] $bindings
     */
    public function addQuery(string $sql, array $bindings = [], float $durationMs = 0.0): void
    {
        $this->queries[] = [
            'sql'         => $sql,
            'bindings'    => $bindings,
            'duration_ms' => round($durationMs, 3),
        ];
    }

    public function getName(): string
    {
        return 'queries';
    }

    public function collect(): array
    {
        return [
            'count'   => count($this->queries),
            'total_ms' => array_sum(array_column($this->queries, 'duration_ms')),
            'queries' => $this->queries,
        ];
    }
}
