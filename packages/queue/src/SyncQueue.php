<?php

declare(strict_types=1);

namespace Nextphp\Queue;

/**
 * Synchronous queue — executes jobs immediately on push().
 * Intended for local development and testing environments.
 */
final class SyncQueue implements QueueInterface
{
    public function push(JobInterface $job): void
    {
        $job->handle();
    }

    public function pushDelayed(JobInterface $job, int $delaySeconds): void
    {
        // In sync mode delay is not meaningful — execute immediately.
        $job->handle();
    }

    public function pop(): ?QueuedJob
    {
        return null;
    }

    public function size(): int
    {
        return 0;
    }
}
