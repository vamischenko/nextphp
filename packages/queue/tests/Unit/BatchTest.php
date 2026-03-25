<?php

declare(strict_types=1);

namespace Nextphp\Queue\Tests\Unit;

use Nextphp\Queue\Batch\Batch;
use Nextphp\Queue\Batch\BatchJobWrapper;
use Nextphp\Queue\InMemoryQueue;
use Nextphp\Queue\JobInterface;
use Nextphp\Queue\Worker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Batch::class)]
#[CoversClass(BatchJobWrapper::class)]
final class BatchTest extends TestCase
{
    private InMemoryQueue $queue;

    protected function setUp(): void
    {
        $this->queue = new InMemoryQueue();
    }

    private function workerRun(): void
    {
        $worker = new Worker($this->queue, maxTries: 1);
        $worker->runAll();
    }

    #[Test]
    public function thenCallbackFiredWhenAllJobsSucceed(): void
    {
        $done = false;

        $batch = new Batch($this->queue);
        $batch->add(new BatchSuccessJob(), new BatchSuccessJob());
        $batch->then(function () use (&$done): void { $done = true; });
        $batch->dispatch();

        $this->workerRun();

        self::assertTrue($done);
    }

    #[Test]
    public function catchCallbackFiredOnFailure(): void
    {
        $caught = false;

        $batch = new Batch($this->queue);
        $batch->add(new BatchFailingJob());
        $batch->catch(function () use (&$caught): void { $caught = true; });
        $batch->dispatch();

        $this->workerRun();

        self::assertTrue($caught);
    }

    #[Test]
    public function thenNotCalledWhenAnyJobFails(): void
    {
        $done = false;

        $batch = new Batch($this->queue);
        $batch->add(new BatchSuccessJob(), new BatchFailingJob());
        $batch->then(function () use (&$done): void { $done = true; });
        $batch->dispatch();

        $this->workerRun();

        self::assertFalse($done);
    }

    #[Test]
    public function finallyCallbackAlwaysFired(): void
    {
        $finallyCount = 0;

        $batch1 = new Batch($this->queue);
        $batch1->add(new BatchSuccessJob());
        $batch1->finally(function () use (&$finallyCount): void { $finallyCount++; });
        $batch1->dispatch();
        $this->workerRun();

        $batch2 = new Batch($this->queue);
        $batch2->add(new BatchFailingJob());
        $batch2->finally(function () use (&$finallyCount): void { $finallyCount++; });
        $batch2->dispatch();
        $this->workerRun();

        self::assertSame(2, $finallyCount);
    }

    #[Test]
    public function batchCountsAreAccurate(): void
    {
        $batch = new Batch($this->queue);
        $batch->add(new BatchSuccessJob(), new BatchSuccessJob(), new BatchFailingJob());
        $batch->dispatch();

        $this->workerRun();

        self::assertSame(2, $batch->processedCount());
        self::assertSame(1, $batch->failedCount());
        self::assertTrue($batch->isFinished());
    }

    #[Test]
    public function batchIsNotFinishedBeforeDispatch(): void
    {
        $batch = new Batch($this->queue);
        $batch->add(new BatchSuccessJob());

        self::assertFalse($batch->isFinished());
    }
}

// ---- Fixtures ----

final class BatchSuccessJob implements JobInterface
{
    public function handle(): void
    {
        // success
    }
}

final class BatchFailingJob implements JobInterface
{
    public function handle(): void
    {
        throw new \RuntimeException('Job failed');
    }
}
