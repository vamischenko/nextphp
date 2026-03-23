<?php

declare(strict_types=1);

namespace Nextphp\Core\Async;

final class FiberScheduler
{
    /** @var list<Task> */
    private array $tasks = [];

    /**
     * @param callable(): mixed $callback
     */
    public function async(callable $callback): Task
    {
        $task = new Task(new \Fiber($callback));
        $this->tasks[] = $task;
        $task->start();

        return $task;
    }

    public function run(): void
    {
        do {
            $pending = false;
            foreach ($this->tasks as $task) {
                if (! $task->isCompleted()) {
                    $pending = true;
                    $task->resume();
                }
            }
        } while ($pending);
    }

    public static function suspend(mixed $value = null): mixed
    {
        return \Fiber::suspend($value);
    }
}
