<?php

declare(strict_types=1);

namespace Nextphp\Queue;

final class InMemoryQueue implements QueueInterface
{
    /** @var array<int, array{job: QueuedJob, availableAt: int}> */
    private array $items = [];

    public function push(JobInterface $job): void
    {
        $this->items[] = [
            'job' => new QueuedJob($job),
            'availableAt' => time(),
        ];
    }

    public function pushDelayed(JobInterface $job, int $delaySeconds): void
    {
        $this->items[] = [
            'job' => new QueuedJob($job),
            'availableAt' => time() + max($delaySeconds, 0),
        ];
    }

    public function pop(): ?QueuedJob
    {
        $now = time();
        foreach ($this->items as $index => $item) {
            if ($item['availableAt'] <= $now) {
                unset($this->items[$index]);
                $this->items = array_values($this->items);

                return $item['job'];
            }
        }

        return null;
    }

    public function size(): int
    {
        return count($this->items);
    }
}
