<?php

declare(strict_types=1);

namespace Nextphp\Debugbar;

use Nextphp\Debugbar\Collector\CollectorInterface;

/**
 * Central registry that holds all collectors and passes data to the renderer.
 */
final class DebugBar
{
    /** @var CollectorInterface[] */
    private array $collectors = [];

    private bool $enabled;

    /**
      * @psalm-mutation-free
     */
    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Return the named collector (or null if not registered).
       * @psalm-mutation-free
     */
    public function getCollector(string $name): ?CollectorInterface
    {
        return $this->collectors[$name] ?? null;
    }

    /**
     * Collect data from all registered collectors.
     *
     * @return array<string, array<string, mixed>>
     */
    public function collectAll(): array
    {
        $data = [];
        foreach ($this->collectors as $name => $collector) {
            $data[$name] = $collector->collect();
        }

        return $data;
    }
}
