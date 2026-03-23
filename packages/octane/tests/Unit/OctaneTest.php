<?php

declare(strict_types=1);

namespace Nextphp\Octane\Tests\Unit;

use Nextphp\Octane\Adapter\RoadRunnerRuntime;
use Nextphp\Octane\Adapter\SwooleRuntime;
use Nextphp\Octane\Octane;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Octane::class)]
#[CoversClass(SwooleRuntime::class)]
#[CoversClass(RoadRunnerRuntime::class)]
final class OctaneTest extends TestCase
{
    #[Test]
    public function runsSwooleRuntimeCallbacks(): void
    {
        $state = ['boot' => 0, 'tick' => 0];
        $octane = new Octane(new SwooleRuntime());
        $octane->run(
            function () use (&$state): void {
                $state['boot']++;
            },
            function () use (&$state): void {
                $state['tick']++;
            },
        );

        self::assertSame(1, $state['boot']);
        self::assertSame(1, $state['tick']);
    }

    #[Test]
    public function runsRoadRunnerRuntimeCallbacks(): void
    {
        $state = ['boot' => 0, 'tick' => 0];
        $octane = new Octane(new RoadRunnerRuntime());
        $octane->run(
            function () use (&$state): void {
                $state['boot']++;
            },
            function () use (&$state): void {
                $state['tick']++;
            },
        );

        self::assertSame(1, $state['boot']);
        self::assertSame(1, $state['tick']);
    }
}
