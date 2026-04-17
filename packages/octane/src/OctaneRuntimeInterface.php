<?php

declare(strict_types=1);

namespace Nextphp\Octane;

/**
 * @psalm-mutable
 */
interface OctaneRuntimeInterface
{
    /**
     * @param callable(): void $bootstrap
     */
    /**
     * @psalm-impure
     */
    public function boot(callable $bootstrap): void;

    /**
     * @param callable(): void $tick
     */
    /**
     * @psalm-impure
     */
    public function loop(callable $tick): void;

    /**
     * @psalm-impure
     */
    public function stop(): void;
}
