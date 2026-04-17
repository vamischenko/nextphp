<?php

declare(strict_types=1);

namespace Nextphp\Queue;

/**
 * @psalm-mutable
 */
interface JobInterface
{
    /**
     * @psalm-impure
     */
    public function handle(): void;
}
