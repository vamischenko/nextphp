<?php

declare(strict_types=1);

namespace Nextphp\Queue\Batch;

use Nextphp\Queue\JobInterface;
use Nextphp\Queue\QueueInterface;

/**
 * A Batch groups multiple jobs and tracks their collective completion.
 *
 * Usage:
 *   $batch = new Batch($queue);
 *   $batch->add(new SendEmail($user1));
 *   $batch->add(new SendEmail($user2));
 *   $batch->then(function () { echo 'All done!'; });
 *   $batch->dispatch();
 */
final class Batch
{
    /** @var JobInterface[] */
    private array $jobs = [];

    private int $pending   = 0;
    private int $processed = 0;
    private int $failed    = 0;

    /** @var array<int, callable(): void> */
    private array $thenCallbacks = [];

    /** @var array<int, callable(): void> */
    private array $catchCallbacks = [];

    /** @var array<int, callable(): void> */
    private array $finallyCallbacks = [];

    private bool $dispatched = false;

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly QueueInterface $queue,
    ) {
    }

    /**
      * @psalm-external-mutation-free
     */
    public function add(JobInterface ...$jobs): static
    {
        foreach ($jobs as $job) {
            $this->jobs[] = $job;
        }

        return $this;
    }

    /** Called when ALL jobs in the batch succeed. */
    /**
      * @psalm-external-mutation-free
     */
    public function then(callable $callback): static
    {
        $this->thenCallbacks[] = $callback;

        return $this;
    }

    /** Called when ANY job in the batch fails. */
    /**
      * @psalm-external-mutation-free
     */
    public function catch(callable $callback): static
    {
        $this->catchCallbacks[] = $callback;

        return $this;
    }

    /** Called after the entire batch finishes (success or failure). */
    /**
      * @psalm-external-mutation-free
     */
    public function finally(callable $callback): static
    {
        $this->finallyCallbacks[] = $callback;

        return $this;
    }

    /**
     * Dispatch all jobs, wrapping each in a BatchJobWrapper that reports back.
     */
    public function dispatch(): void
    {
        $this->pending = count($this->jobs);

        foreach ($this->jobs as $job) {
            $this->queue->push(new BatchJobWrapper($job, $this));
        }

        $this->dispatched = true;
    }

    /** Called internally by BatchJobWrapper on success. */
    public function reportSuccess(): void
    {
        $this->processed++;
        $this->checkCompletion();
    }

    /** Called internally by BatchJobWrapper on failure. */
    public function reportFailure(): void
    {
        $this->failed++;
        foreach ($this->catchCallbacks as $cb) {
            $cb();
        }
        $this->checkCompletion();
    }

    /**
      * @psalm-mutation-free
     */
    public function pendingCount(): int
    {
        return max(0, $this->pending - $this->processed - $this->failed);
    }

    public function processedCount(): int
    {
        return $this->processed;
    }

    public function failedCount(): int
    {
        return $this->failed;
    }

    /**
      * @psalm-mutation-free
     */
    public function isFinished(): bool
    {
        return $this->dispatched && $this->pendingCount() === 0;
    }

    private function checkCompletion(): void
    {
        if ($this->pendingCount() > 0) {
            return;
        }

        if ($this->failed === 0) {
            foreach ($this->thenCallbacks as $cb) {
                $cb();
            }
        }

        foreach ($this->finallyCallbacks as $cb) {
            $cb();
        }
    }
}
