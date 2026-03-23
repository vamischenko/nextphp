<?php

declare(strict_types=1);

namespace Nextphp\Orm\Migration;

use Nextphp\Orm\Connection\ConnectionInterface;

final class Migrator
{
    private const MIGRATIONS_TABLE = 'migrations';

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $migrationsPath,
    ) {
        $this->ensureMigrationsTable();
    }

    /**
     * Run all pending migrations.
     *
     * @return string[] names of run migrations
     */
    public function migrate(): array
    {
        $pending = $this->getPendingMigrations();
        $ran = [];

        foreach ($pending as $file) {
            $migration = $this->resolve($file);
            $migration->setConnection($this->connection);

            $this->connection->transaction(function () use ($migration, $file, &$ran): void {
                $migration->up();
                $this->logMigration($file);
                $ran[] = $file;
            });
        }

        return $ran;
    }

    /**
     * Rollback the last batch of migrations.
     *
     * @return string[] names of rolled-back migrations
     */
    public function rollback(): array
    {
        $lastBatch = $this->getLastBatch();

        if ($lastBatch === []) {
            return [];
        }

        $rolledBack = [];

        foreach (array_reverse($lastBatch) as $name) {
            $file = $this->findFile($name);

            if ($file === null) {
                continue;
            }

            $migration = $this->resolve($file);
            $migration->setConnection($this->connection);

            $this->connection->transaction(function () use ($migration, $name, &$rolledBack): void {
                $migration->down();
                $this->removeMigration($name);
                $rolledBack[] = $name;
            });
        }

        return $rolledBack;
    }

    /**
     * Reset all migrations (rollback everything).
     *
     * @return string[]
     */
    public function reset(): array
    {
        $all = $this->getRan();
        $rolled = [];

        foreach (array_reverse($all) as $name) {
            $file = $this->findFile($name);

            if ($file === null) {
                continue;
            }

            $migration = $this->resolve($file);
            $migration->setConnection($this->connection);

            $this->connection->transaction(function () use ($migration, $name, &$rolled): void {
                $migration->down();
                $this->removeMigration($name);
                $rolled[] = $name;
            });
        }

        return $rolled;
    }

    /**
     * @return string[]
     */
    public function getPendingMigrations(): array
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRan();

        return array_values(array_filter(
            $files,
            fn ($file) => ! in_array($this->getMigrationName($file), $ran, true),
        ));
    }

    /**
     * @return string[]
     */
    public function getRan(): array
    {
        $rows = $this->connection->select(
            'SELECT migration FROM ' . self::MIGRATIONS_TABLE . ' ORDER BY batch ASC, migration ASC',
        );

        return array_column($rows, 'migration');
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function ensureMigrationsTable(): void
    {
        $this->connection->statement(
            'CREATE TABLE IF NOT EXISTS ' . self::MIGRATIONS_TABLE . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )',
        );
    }

    /**
     * @return string[]
     */
    private function getMigrationFiles(): array
    {
        if (! is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files);

        return $files;
    }

    private function resolve(string $file): Migration
    {
        require_once $file;

        $class = $this->getClassFromFile($file);

        return new $class();
    }

    private function getClassFromFile(string $file): string
    {
        $content = file_get_contents($file) ?: '';

        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $content, $matches)) {
            return $matches[1];
        }

        throw new \RuntimeException('Cannot find Migration class in: ' . $file);
    }

    private function getMigrationName(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    private function logMigration(string $file): void
    {
        $batch = $this->getNextBatchNumber();

        $this->connection->insert(
            'INSERT INTO ' . self::MIGRATIONS_TABLE . ' (migration, batch) VALUES (?, ?)',
            [$this->getMigrationName($file), $batch],
        );
    }

    private function removeMigration(string $name): void
    {
        $this->connection->affectingStatement(
            'DELETE FROM ' . self::MIGRATIONS_TABLE . ' WHERE migration = ?',
            [$name],
        );
    }

    private function getNextBatchNumber(): int
    {
        $result = $this->connection->selectOne(
            'SELECT MAX(batch) as max_batch FROM ' . self::MIGRATIONS_TABLE,
        );

        return (int) ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * @return string[]
     */
    private function getLastBatch(): array
    {
        $result = $this->connection->selectOne(
            'SELECT MAX(batch) as max_batch FROM ' . self::MIGRATIONS_TABLE,
        );

        if ($result === null || $result['max_batch'] === null) {
            return [];
        }

        $rows = $this->connection->select(
            'SELECT migration FROM ' . self::MIGRATIONS_TABLE . ' WHERE batch = ?',
            [(int) $result['max_batch']],
        );

        return array_column($rows, 'migration');
    }

    private function findFile(string $name): ?string
    {
        foreach ($this->getMigrationFiles() as $file) {
            if ($this->getMigrationName($file) === $name) {
                return $file;
            }
        }

        return null;
    }
}
