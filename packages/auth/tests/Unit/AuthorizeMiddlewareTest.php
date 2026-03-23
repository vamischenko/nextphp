<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\AuthorizationException;
use Nextphp\Auth\AuthorizeMiddleware;
use Nextphp\Auth\PolicyRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthorizeMiddleware::class)]
final class AuthorizeMiddlewareTest extends TestCase
{
    #[Test]
    public function allowsExecutionWhenPolicyPasses(): void
    {
        $policies = new PolicyRegistry();
        $policies->define('edit', static fn (string $role): bool => $role === 'admin');

        $middleware = new AuthorizeMiddleware($policies);
        $result = $middleware->handle('edit', static fn (): string => 'ok', 'admin');

        self::assertSame('ok', $result);
    }

    #[Test]
    public function throwsWhenPolicyFails(): void
    {
        $policies = new PolicyRegistry();
        $policies->define('edit', static fn (string $role): bool => $role === 'admin');

        $middleware = new AuthorizeMiddleware($policies);

        $this->expectException(AuthorizationException::class);
        $middleware->handle('edit', static fn (): string => 'ok', 'user');
    }
}
