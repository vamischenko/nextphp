<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Seeder\Seeder;
use Nextphp\Orm\Seeder\SeederRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Seeder::class)]
#[CoversClass(SeederRunner::class)]
final class SeederTest extends TestCase
{
    #[Test]
    public function runnerRunsSingleSeeder(): void
    {
        TrackingSeeder::$ran = false;

        $runner = new SeederRunner();
        $runner->run(TrackingSeeder::class);

        self::assertTrue(TrackingSeeder::$ran);
    }

    #[Test]
    public function runnerRunsAllRegistered(): void
    {
        TrackingSeeder::$ran  = false;
        TrackingSeeder2::$ran = false;

        $runner = new SeederRunner();
        $runner->register(TrackingSeeder::class, TrackingSeeder2::class)->runAll();

        self::assertTrue(TrackingSeeder::$ran);
        self::assertTrue(TrackingSeeder2::$ran);
    }

    #[Test]
    public function seederCanCallAnotherSeeder(): void
    {
        TrackingSeeder::$ran = false;

        $runner = new SeederRunner();
        $runner->run(ParentSeeder::class);

        self::assertTrue(TrackingSeeder::$ran);
    }

    #[Test]
    public function seederCallAllRunsMultiple(): void
    {
        TrackingSeeder::$ran  = false;
        TrackingSeeder2::$ran = false;

        $runner = new SeederRunner();
        $runner->run(BulkSeeder::class);

        self::assertTrue(TrackingSeeder::$ran);
        self::assertTrue(TrackingSeeder2::$ran);
    }

    #[Test]
    public function runnerDiscoverSkipsMissingDirectory(): void
    {
        $runner = new SeederRunner();
        $runner->discover('/nonexistent/path')->runAll(); // must not throw
        $this->addToAssertionCount(1);
    }
}

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

final class TrackingSeeder extends Seeder
{
    public static bool $ran = false;

    public function run(): void
    {
        self::$ran = true;
    }
}

final class TrackingSeeder2 extends Seeder
{
    public static bool $ran = false;

    public function run(): void
    {
        self::$ran = true;
    }
}

final class ParentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TrackingSeeder::class);
    }
}

final class BulkSeeder extends Seeder
{
    public function run(): void
    {
        $this->callAll([TrackingSeeder::class, TrackingSeeder2::class]);
    }
}
