<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\InMemoryTokenStore;
use Nextphp\Auth\TokenGuard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenGuard::class)]
final class TokenGuardTest extends TestCase
{
    #[Test]
    public function issueAuthenticateAndRevokeToken(): void
    {
        $guard = new TokenGuard(new InMemoryTokenStore());
        $token = $guard->issueFor('42');

        self::assertSame('42', $guard->authenticate($token));
        $guard->revoke($token);
        self::assertNull($guard->authenticate($token));
    }
}
