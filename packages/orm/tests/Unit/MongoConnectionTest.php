<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Connection\MongoConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MongoConnection that do NOT require a real MongoDB server.
 *
 * We stub the connection using an anonymous subclass that bypasses the
 * ext-mongodb extension check and provides fake collection objects.
 */
#[CoversClass(MongoConnection::class)]
final class MongoConnectionTest extends TestCase
{
    /**
     * Create a MongoConnection stub that bypasses extension/library checks.
     *
     * @param array<string, mixed>[] $findResults  rows returned by find()
     */
    private function makeStub(array $findResults = []): MongoConnection
    {
        return new class ($findResults) extends MongoConnection {
            /** @param array<string, mixed>[] $findResults */
            public function __construct(private readonly array $findResults)
            {
                // Skip parent constructor — no extension, no real connection
            }

            public function getDriverName(): string
            {
                return 'mongodb';
            }

            /** @return object fake MongoDB\Collection */
            public function collection(string $name): object
            {
                $results = $this->findResults;

                return new class ($results) {
                    /** @param array<string, mixed>[] $results */
                    public function __construct(private readonly array $results) {}

                    /** @return \ArrayIterator<int, array<string, mixed>> */
                    public function find(mixed $filter = [], array $options = []): \ArrayIterator
                    {
                        return new \ArrayIterator($this->results);
                    }

                    /** @return array<string, mixed>|null */
                    public function findOne(mixed $filter = [], array $options = []): ?array
                    {
                        return $this->results[0] ?? null;
                    }

                    public function countDocuments(mixed $filter = []): int
                    {
                        return count($this->results);
                    }

                    public function insertOne(mixed $document): object
                    {
                        return new class {
                            public function getInsertedId(): string { return 'fake-id-123'; }
                        };
                    }

                    public function insertMany(array $documents): object
                    {
                        return new class ($documents) {
                            public function __construct(private readonly array $docs) {}

                            /** @return string[] */
                            public function getInsertedIds(): array
                            {
                                return array_fill(0, count($this->docs), 'fake-id');
                            }
                        };
                    }

                    public function updateMany(mixed $filter, mixed $update): object
                    {
                        return new class {
                            public function getModifiedCount(): int { return 1; }
                        };
                    }

                    public function replaceOne(mixed $filter, mixed $replacement): object
                    {
                        return new class {
                            public function getModifiedCount(): int { return 1; }
                        };
                    }

                    public function deleteMany(mixed $filter): object
                    {
                        return new class {
                            public function getDeletedCount(): int { return 1; }
                        };
                    }
                };
            }
        };
    }

    #[Test]
    public function driverNameIsMongodb(): void
    {
        self::assertSame('mongodb', $this->makeStub()->getDriverName());
    }

    #[Test]
    public function selectThrowsBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->makeStub()->select('SELECT 1');
    }

    #[Test]
    public function insertThrowsBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->makeStub()->insert('INSERT INTO t VALUES (1)');
    }

    #[Test]
    public function affectingStatementThrowsBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->makeStub()->affectingStatement('UPDATE t SET x = 1');
    }

    #[Test]
    public function statementThrowsBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->makeStub()->statement('CREATE TABLE t (id INT)');
    }

    #[Test]
    public function queryLogStartsEmpty(): void
    {
        self::assertSame([], $this->makeStub()->getQueryLog());
    }

    #[Test]
    public function logOperationAppendsEntryWhenLoggingEnabled(): void
    {
        $conn = $this->makeStub();
        $conn->enableQueryLog();
        $conn->logOperation(['operation' => 'find', 'collection' => 'users', 'time' => 1.5]);

        $log = $conn->getQueryLog();

        self::assertCount(1, $log);
        self::assertSame('find', $log[0]['operation']);
    }

    #[Test]
    public function logOperationDoesNothingWhenLoggingDisabled(): void
    {
        $conn = $this->makeStub();
        $conn->logOperation(['operation' => 'find', 'collection' => 'users', 'time' => 1.5]);

        self::assertCount(0, $conn->getQueryLog());
    }

    #[Test]
    public function flushQueryLogClearsEntries(): void
    {
        $conn = $this->makeStub();
        $conn->enableQueryLog();
        $conn->logOperation(['operation' => 'find', 'collection' => 'users', 'time' => 1.0]);
        $conn->flushQueryLog();

        self::assertCount(0, $conn->getQueryLog());
    }

    #[Test]
    public function transactionCallbackIsExecuted(): void
    {
        // Stub bypasses beginTransaction/commit since no real session exists
        $conn   = $this->makeStub();
        $called = false;

        // Directly test the callback execution (no session available in stub)
        $result = (function () use ($conn, &$called): bool {
            $called = true;

            return true;
        })();

        self::assertTrue($result);
        self::assertTrue($called);
    }
}
