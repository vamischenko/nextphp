<?php

declare(strict_types=1);

namespace Nextphp\Octane;

interface OctaneRuntimeInterface
{
    /**
     * @param callable(): void $bootstrap
     */
    public function boot(callable $bootstrap): void;

    /**
     * @param callable(): void $tick
     */
    public function loop(callable $tick): void;

    public function stop(): void;
}
