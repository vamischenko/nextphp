<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

use Nextphp\Orm\Exception\QueryException;

/**
 * ConnectionInterface implementation for ClickHouse via HTTP API.
 *
 * ClickHouse does not support PDO. This driver communicates over
 * the ClickHouse HTTP interface (default port 8123) using JSONEachRow format.
 *
 * Transactions: ClickHouse supports transactions since v22.4 only on MergeTree
 * tables with specific settings. For most use-cases they are a no-op here.
 *
 * Prepared statements: ClickHouse HTTP API does not support "?" placeholders,
 * so bindings are interpolated with escaping before sending.
 */
final class ClickHouseConnection implements ConnectionInterface
{
    private bool $loggingEnabled = false;

    /** @var array<string, mixed>[] */
    private array $queryLog = [];

    private readonly HttpClientInterface $httpClient;

    public function __construct(
        private readonly string $host,
        private readonly string $database,
        private readonly string $username = 'default',
        private readonly string $password = '',
        private readonly int $port = 8123,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient();
    }

    public static function create(
        string $host,
        string $database,
        string $username = 'default',
        string $password = '',
        int $port = 8123,
    ): self {
        return new self($host, $database, $username, $password, $port);
    }

    // -------------------------------------------------------------------------
    // ConnectionInterface — DML
    // -------------------------------------------------------------------------

    /**
     * @param mixed[] $bindings
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        $response = $this->execute($sql . ' FORMAT JSONEachRow', $bindings);

        return $this->parseJsonEachRow($response);
    }

    /**
     * @param mixed[] $bindings
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $rows = $this->select($sql, $bindings);

        return $rows[0] ?? null;
    }

    /**
     * @param mixed[] $bindings
     */
    public function insert(string $sql, array $bindings = []): string|false
    {
        $this->execute($sql, $bindings);

        // ClickHouse does not return last insert ID
        return '0';
    }

    /**
     * @param mixed[] $bindings
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        $this->execute($sql, $bindings);

        // ClickHouse does not return affected row count over HTTP API
        return 1;
    }

    /**
     * @param mixed[] $bindings
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        $this->execute($sql, $bindings);

        return true;
    }

    // -------------------------------------------------------------------------
    // ConnectionInterface — Transactions
    // -------------------------------------------------------------------------

    /**
     * ClickHouse transactions require v22.4+ and explicit server-side settings.
     * For most deployments this is effectively a no-op.
     */
    public function beginTransaction(): void
    {
        // No-op for standard ClickHouse deployments
    }

    public function commit(): void
    {
        // No-op
    }

    public function rollBack(): void
    {
        // No-op — ClickHouse does not support statement-level rollback via HTTP
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        // Execute without transactional guarantees
        return $callback();
    }

    // -------------------------------------------------------------------------
    // ConnectionInterface — Metadata
    // -------------------------------------------------------------------------

    public function getDriverName(): string
    {
        return 'clickhouse';
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

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * @param mixed[] $bindings
     */
    private function execute(string $sql, array $bindings): string
    {
        $interpolated = $this->interpolate($sql, $bindings);
        $start        = microtime(true);

        $url = sprintf(
            'http://%s:%d/?database=%s&user=%s&password=%s',
            $this->host,
            $this->port,
            urlencode($this->database),
            urlencode($this->username),
            urlencode($this->password),
        );

        try {
            $response = $this->httpClient->post($url, $interpolated);
        } catch (\RuntimeException $e) {
            throw new QueryException($interpolated, $bindings, $e);
        }

        if ($this->loggingEnabled) {
            $this->queryLog[] = [
                'sql'      => $sql,
                'bindings' => $bindings,
                'time'     => round((microtime(true) - $start) * 1000, 2),
            ];
        }

        return $response;
    }

    /**
     * Interpolate "?" placeholders with escaped values.
     * ClickHouse HTTP API does not support native prepared statements.
     *
     * @param mixed[] $bindings
     */
    private function interpolate(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            $escaped = match (true) {
                $binding === null    => 'NULL',
                is_bool($binding)    => $binding ? '1' : '0',
                is_int($binding)     => (string) $binding,
                is_float($binding)   => (string) $binding,
                default              => "'" . str_replace(
                    ["\\", "'"],
                    ["\\\\", "\\'"],
                    (string) $binding,
                ) . "'",
            };

            $sql = preg_replace('/\?/', $escaped, $sql, 1) ?? $sql;
        }

        return $sql;
    }

    /**
     * Parse JSONEachRow response — one JSON object per line.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseJsonEachRow(string $response): array
    {
        $rows = [];

        foreach (explode("\n", trim($response)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }

        return $rows;
    }
}
