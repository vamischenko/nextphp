<?php

declare(strict_types=1);

namespace Nextphp\Queue;

use PDO;

/**
 * Persists failed jobs to a database table for monitoring and retry.
 *
 * Required table (auto-created via createSchema()):
 *
 *   CREATE TABLE failed_jobs (
 *       id         INTEGER PRIMARY KEY AUTOINCREMENT,
 *       job        TEXT    NOT NULL,
 *       error      TEXT    NOT NULL,
 *       failed_at  INTEGER NOT NULL
 *   );
 */
final class FailedJobStore
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'failed_jobs',
    ) {
    }

    public function store(JobInterface $job, string $error): void
    {
        $this->pdo->prepare(
            "INSERT INTO {$this->table} (job, error, failed_at) VALUES (?, ?, ?)",
        )->execute([serialize($job), $error, time()]);
    }

    /**
     * @return array<int, array{id: int, job: string, error: string, failed_at: int}>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT id, job, error, failed_at FROM {$this->table} ORDER BY id DESC");

        /** @var array<int, array{id: int, job: string, error: string, failed_at: int}> */
        return $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Attempt to re-queue a failed job by its ID.
     */
    public function retry(int $id, QueueInterface $queue): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT job FROM {$this->table} WHERE id = ?",
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return false;
        }

        $job = unserialize((string) $row['job']);

        if (!$job instanceof JobInterface) {
            return false;
        }

        $queue->push($job);
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);

        return true;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }

    public function flush(): void
    {
        $this->pdo->exec("DELETE FROM {$this->table}");
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");

        return $stmt !== false ? (int) $stmt->fetchColumn() : 0;
    }

    public function createSchema(): void
    {
        $this->pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS {$this->table} (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            job       TEXT    NOT NULL,
            error     TEXT    NOT NULL,
            failed_at INTEGER NOT NULL
        )
        SQL);
    }
}
