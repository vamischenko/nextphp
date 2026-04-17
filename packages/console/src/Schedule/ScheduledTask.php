<?php

declare(strict_types=1);

namespace Nextphp\Console\Schedule;

/**
 * A single scheduled task with a cron expression and fluent builder API.
 *
 * Supported frequencies (fluent):
 *   ->everyMinute()
 *   ->everyFiveMinutes()
 *   ->everyTenMinutes()
 *   ->everyFifteenMinutes()
 *   ->everyThirtyMinutes()
 *   ->hourly()
 *   ->hourlyAt(15)          // at :15 of every hour
 *   ->daily()
 *   ->dailyAt('08:30')
 *   ->weekdays()            // combined with dailyAt / hourly
 *   ->weekly()              // Sunday 00:00
 *   ->weeklyOn(1, '09:00') // Monday 09:00  (0=Sun … 6=Sat)
 *   ->monthly()
 *   ->monthlyOn(15, '12:00')
 *   ->cron('* * * * *')    // raw expression
 */
final class ScheduledTask
{
    private string $expression = '* * * * *';

    /** @var list<int> weekday restriction (0–6, 0=Sun) or empty = all */
    private array $weekdayFilter = [];

    private string $description = '';

    private bool $ran = false;

    /**
     * @param callable(): void | null  $callback
     * @param list<string>             $commandArgs
       * @psalm-mutation-free
     */
    public function __construct(
        private readonly mixed $callback,
        private readonly ?string $commandName = null,
        private readonly array $commandArgs = [],
    ) {
    }

    // -------------------------------------------------------------------------
    // Frequency helpers
    // -------------------------------------------------------------------------

    /**
      * @psalm-external-mutation-free
     */
    public function cron(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function everyTenMinutes(): self
    {
        return $this->cron('*/10 * * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function everyFifteenMinutes(): self
    {
        return $this->cron('*/15 * * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function everyThirtyMinutes(): self
    {
        return $this->cron('*/30 * * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function hourlyAt(int $minute): self
    {
        return $this->cron(sprintf('%d * * * *', $minute));
    }

    /**
      * @psalm-external-mutation-free
     */
    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function dailyAt(string $time): self
    {
        [$hour, $minute] = explode(':', $time) + [0, '0'];
        return $this->cron(sprintf('%d %d * * *', (int) $minute, (int) $hour));
    }

    /**
      * @psalm-external-mutation-free
     */
    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function weeklyOn(int $weekday, string $time = '00:00'): self
    {
        [$hour, $minute] = explode(':', $time) + [0, '0'];
        return $this->cron(sprintf('%d %d * * %d', (int) $minute, (int) $hour, $weekday));
    }

    /**
      * @psalm-external-mutation-free
     */
    public function monthly(): self
    {
        return $this->cron('0 0 1 * *');
    }

    /**
      * @psalm-external-mutation-free
     */
    public function monthlyOn(int $day = 1, string $time = '00:00'): self
    {
        [$hour, $minute] = explode(':', $time) + [0, '0'];
        return $this->cron(sprintf('%d %d %d * *', (int) $minute, (int) $hour, $day));
    }

    /**
     * Restrict execution to weekdays only (Mon–Fri).
       * @psalm-external-mutation-free
     */
    public function weekdays(): self
    {
        $this->weekdayFilter = [1, 2, 3, 4, 5];
        return $this;
    }

    /**
     * Restrict execution to weekends only (Sat–Sun).
       * @psalm-external-mutation-free
     */
    public function weekends(): self
    {
        $this->weekdayFilter = [0, 6];
        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function description(string $desc): self
    {
        $this->description = $desc;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Due check
    // -------------------------------------------------------------------------

    public function isDue(\DateTimeInterface $now): bool
    {
        // Weekday filter
        if ($this->weekdayFilter !== [] && !in_array((int) $now->format('w'), $this->weekdayFilter, true)) {
            return false;
        }

        return CronExpression::matches($this->expression, $now);
    }

    // -------------------------------------------------------------------------
    // Execution
    // -------------------------------------------------------------------------

    public function execute(): void
    {
        $this->ran = true;

        if ($this->callback !== null) {
            ($this->callback)();
            return;
        }

        if ($this->commandName !== null) {
            // Delegate to shell — simplest approach for a monolithic runner
            $args = implode(' ', array_map('escapeshellarg', $this->commandArgs));
            $cmd  = sprintf('php nextphp %s %s', escapeshellarg($this->commandName), $args);
            passthru($cmd);
        }
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
      * @psalm-mutation-free
     */
    public function getDescription(): string
    {
        return $this->description !== ''
            ? $this->description
            : ($this->commandName ?? 'closure');
    }

    public function hasRan(): bool
    {
        return $this->ran;
    }
}
