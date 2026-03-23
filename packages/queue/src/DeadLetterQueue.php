<?php

declare(strict_types=1);

namespace Nextphp\Queue;

final class DeadLetterQueue
{
    /** @var array<int, array{job: JobInterface, error: string}> */
    private array $items = [];

    public function push(JobInterface $job, string $error): void
    {
        $this->items[] = ['job' => $job, 'error' => $error];
    }

    /**
     * @return array<int, array{job: JobInterface, error: string}>
     */
    public function all(): array
    {
        return $this->items;
    }
}
