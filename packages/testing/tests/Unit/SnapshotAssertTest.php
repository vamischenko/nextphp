<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Testing\Snapshot\SnapshotAssert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SnapshotAssert::class)]
final class SnapshotAssertTest extends TestCase
{
    #[Test]
    public function createsAndValidatesSnapshot(): void
    {
        $dir = sys_get_temp_dir() . '/nextphp_snapshots_' . uniqid();
        $assert = new SnapshotAssert($dir);

        $assert->assert('body', 'hello');
        $assert->assert('body', 'hello');

        self::assertFileExists($dir . '/body.snap');
    }
}
