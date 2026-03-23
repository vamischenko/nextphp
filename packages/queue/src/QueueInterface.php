<?php

declare(strict_types=1);

namespace Nextphp\Queue;

interface QueueInterface
{
    public function push(JobInterface $job): void;

    public function pushDelayed(JobInterface $job, int $delaySeconds): void;

    public function pop(): ?QueuedJob;

    public function size(): int;
}
