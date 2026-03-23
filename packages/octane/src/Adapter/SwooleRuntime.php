<?php

declare(strict_types=1);

namespace Nextphp\Octane\Adapter;

use Nextphp\Octane\OctaneRuntimeInterface;

final class SwooleRuntime implements OctaneRuntimeInterface
{
    private bool $running = false;

    public function boot(callable $bootstrap): void
    {
        $bootstrap();
        $this->running = true;
    }

    public function loop(callable $tick): void
    {
        if (! $this->running) {
            return;
        }

        $tick();
    }

    public function stop(): void
    {
        $this->running = false;
    }
}
