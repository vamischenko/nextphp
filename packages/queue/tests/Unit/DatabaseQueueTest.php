<?php

declare(strict_types=1);

namespace Nextphp\Queue\Tests\Unit;

use Nextphp\Queue\DatabaseQueue;
use Nextphp\Queue\JobInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseQueue::class)]
final class DatabaseQueueTest extends TestCase
{
    #[Test]
    public function persistsJobsToStorageFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'nextphp_queue_');
        self::assertNotFalse($file);

        $queue = new DatabaseQueue($file);
        $queue->push(new StubJob());

        self::assertSame(1, $queue->size());
        self::assertNotNull($queue->pop());
    }
}

final class StubJob implements JobInterface
{
    public function handle(): void
    {
    }
}
