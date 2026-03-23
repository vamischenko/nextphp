<?php

declare(strict_types=1);

namespace Nextphp\Queue;

/**
 * Lightweight file-backed queue as persistent driver baseline.
 */
final class DatabaseQueue implements QueueInterface
{
    public function __construct(
        private readonly string $storageFile,
    ) {
        if (! is_file($this->storageFile)) {
            file_put_contents($this->storageFile, json_encode([]));
        }
    }

    public function push(JobInterface $job): void
    {
        $this->append($job, 0);
    }

    public function pushDelayed(JobInterface $job, int $delaySeconds): void
    {
        $this->append($job, max(0, $delaySeconds));
    }

    public function pop(): ?QueuedJob
    {
        $rows = $this->readRows();
        $now = time();

        foreach ($rows as $index => $row) {
            if (($row['available_at'] ?? 0) <= $now) {
                unset($rows[$index]);
                $this->writeRows(array_values($rows));

                return new QueuedJob(unserialize((string) $row['payload']));
            }
        }

        return null;
    }

    public function size(): int
    {
        return count($this->readRows());
    }

    private function append(JobInterface $job, int $delay): void
    {
        $rows = $this->readRows();
        $rows[] = [
            'payload' => serialize($job),
            'available_at' => time() + $delay,
        ];
        $this->writeRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRows(): array
    {
        $content = (string) file_get_contents($this->storageFile);
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function writeRows(array $rows): void
    {
        file_put_contents($this->storageFile, json_encode($rows, JSON_THROW_ON_ERROR));
    }
}
