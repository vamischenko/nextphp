<?php

declare(strict_types=1);

namespace Nextphp\Core\Tests\Unit\Async;

use Nextphp\Core\Async\FiberScheduler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FiberScheduler::class)]
final class FiberSchedulerTest extends TestCase
{
    #[Test]
    public function runsMultipleTasksAndReturnsResults(): void
    {
        $scheduler = new FiberScheduler();
        $trace = [];

        $taskA = $scheduler->async(function () use (&$trace): string {
            $trace[] = 'a:start';
            FiberScheduler::suspend();
            $trace[] = 'a:end';

            return 'A';
        });

        $taskB = $scheduler->async(function () use (&$trace): string {
            $trace[] = 'b:start';
            FiberScheduler::suspend();
            $trace[] = 'b:end';

            return 'B';
        });

        $scheduler->run();

        self::assertSame(['a:start', 'b:start', 'a:end', 'b:end'], $trace);
        self::assertSame('A', $taskA->await());
        self::assertSame('B', $taskB->await());
    }
}
