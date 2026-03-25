<?php

declare(strict_types=1);

namespace Nextphp\Console\Tests\Unit;

use Nextphp\Console\Schedule\CronExpression;
use Nextphp\Console\Schedule\ScheduledTask;
use Nextphp\Console\Schedule\Scheduler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Scheduler::class)]
#[CoversClass(ScheduledTask::class)]
#[CoversClass(CronExpression::class)]
final class SchedulerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // CronExpression matching
    // -------------------------------------------------------------------------

    /** @return list<array{string, string, bool}> */
    public static function cronProvider(): array
    {
        return [
            // every minute — always matches
            ['* * * * *',   '2026-03-24 10:05', true],
            // exact minute
            ['5 * * * *',   '2026-03-24 10:05', true],
            ['5 * * * *',   '2026-03-24 10:06', false],
            // exact hour + minute
            ['0 8 * * *',   '2026-03-24 08:00', true],
            ['0 8 * * *',   '2026-03-24 09:00', false],
            // step */5
            ['*/5 * * * *', '2026-03-24 10:00', true],
            ['*/5 * * * *', '2026-03-24 10:05', true],
            ['*/5 * * * *', '2026-03-24 10:03', false],
            // range 1-5
            ['0 1-5 * * *', '2026-03-24 03:00', true],
            ['0 1-5 * * *', '2026-03-24 06:00', false],
            // list
            ['0 8,12,18 * * *', '2026-03-24 12:00', true],
            ['0 8,12,18 * * *', '2026-03-24 10:00', false],
            // day of week (2026-03-24 = Tuesday = 2)
            ['0 9 * * 2',   '2026-03-24 09:00', true],
            ['0 9 * * 1',   '2026-03-24 09:00', false],
            // day of month
            ['0 0 24 * *',  '2026-03-24 00:00', true],
            ['0 0 25 * *',  '2026-03-24 00:00', false],
            // month
            ['0 0 1 3 *',   '2026-03-01 00:00', true],
            ['0 0 1 4 *',   '2026-03-01 00:00', false],
            // range with step
            ['0-30/10 * * * *', '2026-03-24 10:10', true],
            ['0-30/10 * * * *', '2026-03-24 10:11', false],
        ];
    }

    #[Test]
    #[DataProvider('cronProvider')]
    public function cronExpressionMatches(string $expr, string $datetime, bool $expected): void
    {
        $time = new \DateTimeImmutable($datetime);
        self::assertSame($expected, CronExpression::matches($expr, $time));
    }

    // -------------------------------------------------------------------------
    // Scheduler::run dispatches due tasks
    // -------------------------------------------------------------------------

    #[Test]
    public function runExecutesDueTask(): void
    {
        $scheduler = new Scheduler();
        $ran       = false;

        $scheduler->call(function () use (&$ran): void {
            $ran = true;
        })->everyMinute();

        $scheduler->run(new \DateTimeImmutable('2026-03-24 10:00'));
        self::assertTrue($ran);
    }

    #[Test]
    public function runSkipsNotDueTask(): void
    {
        $scheduler = new Scheduler();
        $ran       = false;

        // daily at 08:00 — not due at 10:00
        $scheduler->call(function () use (&$ran): void {
            $ran = true;
        })->dailyAt('08:00');

        $scheduler->run(new \DateTimeImmutable('2026-03-24 10:00'));
        self::assertFalse($ran);
    }

    #[Test]
    public function runExecutesMultipleDueTasks(): void
    {
        $scheduler = new Scheduler();
        $count     = 0;

        $scheduler->call(function () use (&$count): void { $count++; })->everyMinute();
        $scheduler->call(function () use (&$count): void { $count++; })->hourly();
        $scheduler->call(function () use (&$count): void { $count++; })->daily(); // not due at 10:00

        $scheduler->run(new \DateTimeImmutable('2026-03-24 10:00'));
        self::assertSame(2, $count); // everyMinute + hourly (minute=0, hour=10)
    }

    // -------------------------------------------------------------------------
    // Frequency fluent API
    // -------------------------------------------------------------------------

    #[Test]
    public function everyFiveMinutesExpression(): void
    {
        $task = (new Scheduler())->call(static fn() => null)->everyFiveMinutes();
        self::assertSame('*/5 * * * *', $task->getExpression());
    }

    #[Test]
    public function hourlyAtExpression(): void
    {
        $task = (new Scheduler())->call(static fn() => null)->hourlyAt(15);
        self::assertSame('15 * * * *', $task->getExpression());
    }

    #[Test]
    public function dailyAtExpression(): void
    {
        $task = (new Scheduler())->call(static fn() => null)->dailyAt('08:30');
        self::assertSame('30 8 * * *', $task->getExpression());
    }

    #[Test]
    public function weeklyOnExpression(): void
    {
        $task = (new Scheduler())->call(static fn() => null)->weeklyOn(1, '09:00');
        self::assertSame('0 9 * * 1', $task->getExpression());
    }

    #[Test]
    public function monthlyOnExpression(): void
    {
        $task = (new Scheduler())->call(static fn() => null)->monthlyOn(15, '12:00');
        self::assertSame('0 12 15 * *', $task->getExpression());
    }

    // -------------------------------------------------------------------------
    // Weekday / weekend filter
    // -------------------------------------------------------------------------

    #[Test]
    public function weekdaysFilterSkipsWeekend(): void
    {
        $scheduler = new Scheduler();
        $ran       = false;

        $scheduler->call(function () use (&$ran): void {
            $ran = true;
        })->everyMinute()->weekdays();

        // 2026-03-22 = Sunday (w=0)
        $scheduler->run(new \DateTimeImmutable('2026-03-22 10:00'));
        self::assertFalse($ran);
    }

    #[Test]
    public function weekdaysFilterRunsOnWeekday(): void
    {
        $scheduler = new Scheduler();
        $ran       = false;

        $scheduler->call(function () use (&$ran): void {
            $ran = true;
        })->everyMinute()->weekdays();

        // 2026-03-24 = Tuesday (w=2)
        $scheduler->run(new \DateTimeImmutable('2026-03-24 10:00'));
        self::assertTrue($ran);
    }

    // -------------------------------------------------------------------------
    // Description & task list
    // -------------------------------------------------------------------------

    #[Test]
    public function taskDescription(): void
    {
        $scheduler = new Scheduler();
        $task      = $scheduler->call(static fn() => null)
            ->everyMinute()
            ->description('Send daily digest');

        self::assertSame('Send daily digest', $task->getDescription());
    }

    #[Test]
    public function schedulerTasksList(): void
    {
        $scheduler = new Scheduler();
        $scheduler->call(static fn() => null)->everyMinute();
        $scheduler->call(static fn() => null)->hourly();

        self::assertCount(2, $scheduler->tasks());
    }

    #[Test]
    public function hasRanAfterExecution(): void
    {
        $scheduler = new Scheduler();
        $task      = $scheduler->call(static fn() => null)->everyMinute();

        self::assertFalse($task->hasRan());
        $scheduler->run(new \DateTimeImmutable('2026-03-24 10:00'));
        self::assertTrue($task->hasRan());
    }
}
