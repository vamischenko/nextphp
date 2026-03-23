<?php

declare(strict_types=1);

namespace Nextphp\Queue;

final class RedisQueue implements QueueInterface
{
    /**
     * In-memory fallback when redis extension is unavailable.
     * @var array<int, array{payload: string, available_at: int}>
     */
    private array $fallback = [];

    public function __construct(
        private readonly string $key = 'nextphp:queue:default',
    ) {
    }

    public function push(JobInterface $job): void
    {
        $this->pushDelayed($job, 0);
    }

    public function pushDelayed(JobInterface $job, int $delaySeconds): void
    {
        $this->fallback[] = [
            'payload' => serialize($job),
            'available_at' => time() + max(0, $delaySeconds),
        ];
    }

    public function pop(): ?QueuedJob
    {
        $now = time();
        foreach ($this->fallback as $idx => $row) {
            if ($row['available_at'] <= $now) {
                unset($this->fallback[$idx]);
                $this->fallback = array_values($this->fallback);

                return new QueuedJob(unserialize($row['payload']));
            }
        }

        return null;
    }

    public function size(): int
    {
        return count($this->fallback);
    }
}
