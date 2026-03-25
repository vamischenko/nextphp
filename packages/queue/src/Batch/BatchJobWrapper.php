<?php

declare(strict_types=1);

namespace Nextphp\Queue\Batch;

use Nextphp\Queue\JobInterface;

/**
 * Wraps a real job so it can report success/failure back to its Batch.
 */
final class BatchJobWrapper implements JobInterface
{
    public function __construct(
        private readonly JobInterface $inner,
        private readonly Batch $batch,
    ) {
    }

    public function handle(): void
    {
        try {
            $this->inner->handle();
            $this->batch->reportSuccess();
        } catch (\Throwable $e) {
            $this->batch->reportFailure();
            throw $e;
        }
    }
}
