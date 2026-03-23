<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

use Nextphp\Orm\Connection\Driver\DriverInterface;
use Nextphp\Orm\Connection\Driver\MysqlDriver;
use Nextphp\Orm\Connection\Driver\PgsqlDriver;
use Nextphp\Orm\Connection\Driver\SqliteDriver;
use Nextphp\Orm\Exception\QueryException;
use Nextphp\Orm\Query\GrammarInterface;
use PDO;
use PDOException;

class PdoConnection implements SqlConnectionInterface
{
    private PDO $pdo;

    private DriverInterface $driver;

    private int $transactionDepth = 0;

    /** @var array<string, mixed>[] */
    private array $queryLog = [];

    private bool $loggingEnabled = false;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
    ) {
        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo    = new PDO($dsn, $username, $password, $options + $defaultOptions);
        $this->driver = $this->resolveDriver($dsn);
    }

    public static function sqlite(string $path = ':memory:'): static
    {
        return new static('sqlite:' . $path);
    }

    public static function mysql(string $host, string $database, string $username, string $password, int $port = 3306): static
    {
        return new static(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
        );
    }

    public static function pgsql(string $host, string $database, string $username, string $password, int $port = 5432): static
    {
        return new static(
            sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database),
            $username,
            $password,
        );
    }

    /**
     * @param mixed[] $bindings
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->run($sql, $bindings);

        return $statement->fetchAll();
    }

    /**
     * @param mixed[] $bindings
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = $this->run($sql, $bindings);
        $result    = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @param mixed[] $bindings
     */
    public function insert(string $sql, array $bindings = []): string|false
    {
        $this->run($sql, $bindings);

        return $this->driver->lastInsertId($this->pdo);
    }

    /**
     * @param mixed[] $bindings
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        $statement = $this->run($sql, $bindings);

        return $statement->rowCount();
    }

    /**
     * @param mixed[] $bindings
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        $this->run($sql, $bindings);

        return true;
    }

    public function beginTransaction(): void
    {
        if ($this->transactionDepth === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec('SAVEPOINT sp' . $this->transactionDepth);
        }

        $this->transactionDepth++;
    }

    public function commit(): void
    {
        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            $this->pdo->commit();
        } else {
            $this->pdo->exec('RELEASE SAVEPOINT sp' . $this->transactionDepth);
        }
    }

    public function rollBack(): void
    {
        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            $this->pdo->rollBack();
        } else {
            $this->pdo->exec('ROLLBACK TO SAVEPOINT sp' . $this->transactionDepth);
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

    public function getDriverName(): string
    {
        return $this->driver->getName();
    }

    public function getGrammar(): GrammarInterface
    {
        return $this->driver;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

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

    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * @param mixed[] $bindings
     */
    private function run(string $sql, array $bindings): \PDOStatement
    {
        $start = microtime(true);

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($bindings);
        } catch (PDOException $e) {
            throw new QueryException($sql, $bindings, $e);
        }

        if ($this->loggingEnabled) {
            $this->queryLog[] = [
                'sql'      => $sql,
                'bindings' => $bindings,
                'time'     => round((microtime(true) - $start) * 1000, 2),
            ];
        }

        return $statement;
    }

    private function resolveDriver(string $dsn): DriverInterface
    {
        return match (true) {
            str_starts_with($dsn, 'sqlite') => new SqliteDriver(),
            str_starts_with($dsn, 'mysql')  => new MysqlDriver(),
            str_starts_with($dsn, 'pgsql')  => new PgsqlDriver(),
            default                          => new SqliteDriver(),
        };
    }
}
