<?php

declare(strict_types=1);

namespace Nextphp\Queue\Tests\Unit;

use Nextphp\Queue\InMemoryQueue;
use Nextphp\Queue\JobInterface;
use Nextphp\Queue\Worker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryQueue::class)]
#[CoversClass(Worker::class)]
final class WorkerTest extends TestCase
{
    #[Test]
    public function workerProcessesJob(): void
    {
        $queue = new InMemoryQueue();
        $job = new IncrementJob();
        $queue->push($job);
        $worker = new Worker($queue);

        $result = $worker->runNext();

        self::assertTrue($result);
        self::assertSame(1, $job->count);
    }

    #[Test]
    public function delayedJobNotAvailableImmediately(): void
    {
        $queue = new InMemoryQueue();
        $queue->pushDelayed(new IncrementJob(), 2);

        self::assertNull($queue->pop());
    }

    #[Test]
    public function workerTracksFailedJobAfterRetries(): void
    {
        $queue = new InMemoryQueue();
        $queue->push(new FailingJob());
        $worker = new Worker($queue, 1);

        $worker->runAll();

        self::assertCount(1, $worker->getFailedJobs());
    }
}

final class IncrementJob implements JobInterface
{
    public int $count = 0;

    public function handle(): void
    {
        $this->count++;
    }
}

final class FailingJob implements JobInterface
{
    public function handle(): void
    {
        throw new \RuntimeException('failed');
    }
}
