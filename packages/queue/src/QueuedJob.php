<?php

declare(strict_types=1);

namespace Nextphp\Queue;

final class QueuedJob
{
    public function __construct(
        public readonly JobInterface $job,
        public int $attempts = 0,
    ) {
    }
}
