<?php

declare(strict_types=1);

namespace Nextphp\Console\Schedule;

/**
 * In-process task scheduler (cron-like).
 *
 * Usage:
 *   $scheduler = new Scheduler();
 *   $scheduler->call(fn() => doSomething())->everyMinute();
 *   $scheduler->command('emails:send')->dailyAt('08:00');
 *
 *   // In your worker loop / long-running process:
 *   while (true) {
 *       $scheduler->run();
 *       sleep(60);
 *   }
 */
final class Scheduler
{
    /** @var list<ScheduledTask> */
    private array $tasks = [];

    /**
     * Register a callable task.
     *
     * @param callable(): void $callback
     */
    public function call(callable $callback): ScheduledTask
    {
        $task          = new ScheduledTask($callback);
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * Register a console command name as a task.
     * The command will be resolved and run via the Application passed to run().
     */
    public function command(string $command, string ...$args): ScheduledTask
    {
        $task          = new ScheduledTask(null, $command, $args);
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * Run all tasks that are due at the given time.
     *
     * @param \DateTimeInterface|null $now defaults to current time
     */
    public function run(?\DateTimeInterface $now = null): void
    {
        $now ??= new \DateTimeImmutable();

        foreach ($this->tasks as $task) {
            if ($task->isDue($now)) {
                $task->execute();
            }
        }
    }

    /** @return list<ScheduledTask> */
    public function tasks(): array
    {
        return $this->tasks;
    }
}
