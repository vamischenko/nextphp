<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

/**
 * ConnectionInterface implementation for MongoDB via ext-mongodb + mongodb/mongodb library.
 *
 * Requirements (suggested, not required):
 *   - ext-mongodb PHP extension
 *   - composer require mongodb/mongodb
 *
 * SQL-based methods (select/insert/affectingStatement etc.) throw BadMethodCallException
 * because MongoDB uses a document-oriented query model, not SQL.
 * Use MongoBuilder or $connection->collection() for actual queries.
 *
 * Transactions require a MongoDB Replica Set or Sharded Cluster (MongoDB 4.0+).
 */
class MongoConnection implements ConnectionInterface
{
    /** @var \MongoDB\Database */
    private object $database;

    /** @var \MongoDB\Driver\Session|null */
    private ?object $session = null;

    private bool $loggingEnabled = false;

    /** @var array<string, mixed>[] */
    private array $queryLog = [];

    /**
     * @param array<string, mixed> $uriOptions    Options passed to MongoDB\Client URI
     * @param array<string, mixed> $driverOptions  Options passed to MongoDB\Client driver
     */
    public function __construct(
        private readonly string $uri,
        private readonly string $databaseName,
        array $uriOptions = [],
        array $driverOptions = [],
    ) {
        $this->assertMongoExtension();

        /** @var \MongoDB\Client $client */
        $client         = new \MongoDB\Client($uri, $uriOptions, $driverOptions);
        $this->database = $client->selectDatabase($databaseName);
    }

    public static function create(string $uri, string $database): self
    {
        return new self($uri, $database);
    }

    // -------------------------------------------------------------------------
    // ConnectionInterface — SQL methods (not supported)
    // -------------------------------------------------------------------------

    /**
     * @param mixed[] $bindings
     * @return array<int, array<string, mixed>>
       * @psalm-pure
     */
    public function select(string $sql, array $bindings = []): array
    {
        throw new \BadMethodCallException(
            'MongoConnection does not support raw SQL. Use MongoBuilder or collection() instead.',
        );
    }

    /**
     * @param mixed[] $bindings
     * @return array<string, mixed>|null
       * @psalm-pure
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        throw new \BadMethodCallException(
            'MongoConnection does not support raw SQL. Use MongoBuilder or collection() instead.',
        );
    }

    /**
     * @param mixed[] $bindings
       * @psalm-pure
     */
    public function insert(string $sql, array $bindings = []): string|false
    {
        throw new \BadMethodCallException(
            'MongoConnection does not support raw SQL. Use MongoBuilder or collection() instead.',
        );
    }

    /**
     * @param mixed[] $bindings
       * @psalm-pure
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        throw new \BadMethodCallException(
            'MongoConnection does not support raw SQL. Use MongoBuilder or collection() instead.',
        );
    }

    /**
     * @param mixed[] $bindings
       * @psalm-pure
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        throw new \BadMethodCallException(
            'MongoConnection does not support raw SQL. Use MongoBuilder or collection() instead.',
        );
    }

    // -------------------------------------------------------------------------
    // ConnectionInterface — Transactions
    // -------------------------------------------------------------------------

    /**
     * Start a MongoDB session and transaction.
     * Requires Replica Set or Sharded Cluster (MongoDB 4.0+).
       * @psalm-external-mutation-free
     */
    public function beginTransaction(): void
    {
        /** @var \MongoDB\Driver\Manager $manager */
        $manager       = $this->database->getManager();
        $this->session = $manager->startSession();
        $this->session->startTransaction();
    }

    /**
      * @psalm-external-mutation-free
     */
    public function commit(): void
    {
        if ($this->session !== null) {
            $this->session->commitTransaction();
            $this->session = null;
        }
    }

    /**
      * @psalm-external-mutation-free
     */
    public function rollBack(): void
    {
        if ($this->session !== null) {
            $this->session->abortTransaction();
            $this->session = null;
        }
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback();
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // ConnectionInterface — Metadata
    // -------------------------------------------------------------------------

    /**
      * @psalm-pure
     */
    public function getDriverName(): string
    {
        return 'mongodb';
    }

    /**
      * @psalm-external-mutation-free
     */
    public function enableQueryLog(): void
    {
        $this->loggingEnabled = true;
    }

    /**
     * @return array<string, mixed>[]
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    // -------------------------------------------------------------------------
    // MongoDB-specific public API
    // -------------------------------------------------------------------------

    /**
     * Get a MongoDB collection by name.
     *
     * @return \MongoDB\Collection
     */
    public function collection(string $name): object
    {
        return $this->database->selectCollection($name);
    }

    /**
     * Get the underlying MongoDB\Database instance.
     *
     * @return \MongoDB\Database
     */
    public function getDatabase(): object
    {
        return $this->database;
    }

    /**
     * Get the active session (for use in collection operations during a transaction).
     *
     * @return \MongoDB\Driver\Session|null
     */
    public function getSession(): ?object
    {
        return $this->session;
    }

    /**
     * Log a MongoDB operation (for use by MongoBuilder).
     *
     * @param array<string, mixed> $entry
       * @psalm-external-mutation-free
     */
    public function logOperation(array $entry): void
    {
        if ($this->loggingEnabled) {
            $this->queryLog[] = $entry;
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function assertMongoExtension(): void
    {
        if (! extension_loaded('mongodb')) {
            throw new \RuntimeException(
                'The "mongodb" PHP extension is required for MongoConnection. '
                . 'Install it with: pecl install mongodb',
            );
        }

        if (! class_exists(\MongoDB\Client::class)) {
            throw new \RuntimeException(
                'The "mongodb/mongodb" Composer package is required for MongoConnection. '
                . 'Install it with: composer require mongodb/mongodb',
            );
        }
    }
}
