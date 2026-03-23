<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\Gate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gate::class)]
final class GateTest extends TestCase
{
    #[Test]
    public function allowsAndDenies(): void
    {
        $gate = new Gate();
        $gate->define('edit-post', static fn (string $role): bool => $role === 'admin');

        self::assertTrue($gate->allows('edit-post', 'admin'));
        self::assertTrue($gate->denies('edit-post', 'user'));
    }
}
