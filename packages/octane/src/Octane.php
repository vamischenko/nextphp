<?php

declare(strict_types=1);

namespace Nextphp\Octane;

final class Octane
{
    public function __construct(
        private readonly OctaneRuntimeInterface $runtime,
    ) {
    }

    /**
     * @param callable(): void $bootstrap
     * @param callable(): void $tick
     */
    public function run(callable $bootstrap, callable $tick): void
    {
        $this->runtime->boot($bootstrap);
        $this->runtime->loop($tick);
    }

    public function stop(): void
    {
        $this->runtime->stop();
    }
}
