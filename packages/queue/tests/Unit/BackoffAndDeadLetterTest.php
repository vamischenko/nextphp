<?php

declare(strict_types=1);

namespace Nextphp\Queue\Tests\Unit;

use Nextphp\Queue\Backoff\ExponentialBackoffStrategy;
use Nextphp\Queue\DeadLetterQueue;
use Nextphp\Queue\InMemoryQueue;
use Nextphp\Queue\JobInterface;
use Nextphp\Queue\Worker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExponentialBackoffStrategy::class)]
#[CoversClass(DeadLetterQueue::class)]
#[CoversClass(Worker::class)]
final class BackoffAndDeadLetterTest extends TestCase
{
    #[Test]
    public function exponentialBackoffGrowsDelay(): void
    {
        $strategy = new ExponentialBackoffStrategy(2, 20);

        self::assertSame(2, $strategy->delaySeconds(1));
        self::assertSame(4, $strategy->delaySeconds(2));
        self::assertSame(8, $strategy->delaySeconds(3));
    }

    #[Test]
    public function pushesToDeadLetterAfterMaxRetries(): void
    {
        $queue = new InMemoryQueue();
        $queue->push(new AlwaysFailingJob());
        $deadLetter = new DeadLetterQueue();
        $worker = new Worker($queue, 1, new ExponentialBackoffStrategy(), $deadLetter);

        $worker->runAll();

        self::assertCount(1, $deadLetter->all());
    }
}

final class AlwaysFailingJob implements JobInterface
{
    public function handle(): void
    {
        throw new \RuntimeException('fail');
    }
}
