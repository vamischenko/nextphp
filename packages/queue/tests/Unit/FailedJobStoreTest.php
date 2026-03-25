<?php

declare(strict_types=1);

namespace Nextphp\Queue\Tests\Unit;

use Nextphp\Queue\FailedJobStore;
use Nextphp\Queue\InMemoryQueue;
use Nextphp\Queue\JobInterface;
use Nextphp\Queue\Worker;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FailedJobStore::class)]
final class FailedJobStoreTest extends TestCase
{
    private PDO $pdo;
    private FailedJobStore $store;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->store = new FailedJobStore($this->pdo);
        $this->store->createSchema();
    }

    #[Test]
    public function storeAndRetrieveFailed(): void
    {
        $job = new SimpleJob();
        $this->store->store($job, 'Something went wrong');

        $all = $this->store->all();
        self::assertCount(1, $all);
        self::assertSame('Something went wrong', $all[0]['error']);
    }

    #[Test]
    public function countReturnsCorrectNumber(): void
    {
        self::assertSame(0, $this->store->count());

        $this->store->store(new SimpleJob(), 'err1');
        $this->store->store(new SimpleJob(), 'err2');

        self::assertSame(2, $this->store->count());
    }

    #[Test]
    public function deleteRemovesRecord(): void
    {
        $this->store->store(new SimpleJob(), 'err');
        $id = $this->store->all()[0]['id'];

        $this->store->delete($id);

        self::assertSame(0, $this->store->count());
    }

    #[Test]
    public function flushRemovesAll(): void
    {
        $this->store->store(new SimpleJob(), 'e1');
        $this->store->store(new SimpleJob(), 'e2');
        $this->store->flush();

        self::assertSame(0, $this->store->count());
    }

    #[Test]
    public function retryRequeuesToQueueAndDeletesRecord(): void
    {
        $job   = new SimpleJob();
        $queue = new InMemoryQueue();
        $this->store->store($job, 'oops');

        $id     = $this->store->all()[0]['id'];
        $result = $this->store->retry($id, $queue);

        self::assertTrue($result);
        self::assertSame(1, $queue->size());
        self::assertSame(0, $this->store->count());
    }

    #[Test]
    public function retryReturnsFalseForUnknownId(): void
    {
        $queue  = new InMemoryQueue();
        $result = $this->store->retry(999, $queue);

        self::assertFalse($result);
    }

    #[Test]
    public function workerPersistsFailedJobsToStore(): void
    {
        $queue  = new InMemoryQueue();
        $worker = new Worker($queue, maxTries: 1, failedJobStore: $this->store);
        $queue->push(new FailJob());
        $worker->runNext();

        self::assertSame(1, $this->store->count());
    }
}

// ---- Fixtures ----

final class SimpleJob implements JobInterface
{
    public function handle(): void {}
}

final class FailJob implements JobInterface
{
    public function handle(): void
    {
        throw new \RuntimeException('fail');
    }
}
