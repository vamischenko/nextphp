<?php

declare(strict_types=1);

namespace Nextphp\Queue;

use Nextphp\Queue\Backoff\BackoffStrategyInterface;
use Nextphp\Queue\Backoff\ExponentialBackoffStrategy;

final class Worker
{
    /** @var array<int, array{job: JobInterface, error: string}> */
    private array $failedJobs = [];

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly QueueInterface $queue,
        private readonly int $maxTries = 3,
        private readonly BackoffStrategyInterface $backoff = new ExponentialBackoffStrategy(),
        private readonly ?DeadLetterQueue $deadLetterQueue = null,
        private readonly ?FailedJobStore $failedJobStore = null,
    ) {
    }

    public function runNext(): bool
    {
        $queued = $this->queue->pop();
        if ($queued === null) {
            return false;
        }

        $queued->attempts++;

        $maxTries = $queued->job instanceof RetryableJobInterface
            ? $queued->job->maxTries()
            : $this->maxTries;

        try {
            $queued->job->handle();

            return true;
        } catch (\Throwable $e) {
            if ($queued->attempts < $maxTries) {
                $baseDelay = $queued->job instanceof RetryableJobInterface
                    ? $queued->job->retryAfterSeconds()
                    : 0;
                $delay = max($baseDelay, $this->backoff->delaySeconds($queued->attempts));
                $this->queue->pushDelayed($queued->job, $delay);
            } else {
                $this->failedJobs[] = [
                    'job' => $queued->job,
                    'error' => $e->getMessage(),
                ];
                $this->deadLetterQueue?->push($queued->job, $e->getMessage());
                $this->failedJobStore?->store($queued->job, $e->getMessage());
            }

            return false;
        }
    }

    public function runAll(): int
    {
        $processed = 0;
        while ($this->queue->size() > 0) {
            $processed++;
            $this->runNext();
        }

        return $processed;
    }

    /**
     * @return array<int, array{job: JobInterface, error: string}>
     */
    public function getFailedJobs(): array
    {
        return $this->failedJobs;
    }
}
