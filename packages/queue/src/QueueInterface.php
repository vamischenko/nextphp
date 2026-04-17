<?php

declare(strict_types=1);

namespace Nextphp\Queue;

/**
 * @psalm-mutable
 */
interface QueueInterface
{
    /**
     * @psalm-impure
     */
    public function push(JobInterface $job): void;

    /**
     * @psalm-impure
     */
    public function pushDelayed(JobInterface $job, int $delaySeconds): void;

    /**
     * @psalm-impure
     */
    public function pop(): ?QueuedJob;

    /**
     * @psalm-impure
     */
    public function size(): int;
}
