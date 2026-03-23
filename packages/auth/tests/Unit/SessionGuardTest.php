<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\ArraySessionStore;
use Nextphp\Auth\PasswordHasher;
use Nextphp\Auth\SessionGuard;
use Nextphp\Auth\UserInterface;
use Nextphp\Auth\UserProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionGuard::class)]
final class SessionGuardTest extends TestCase
{
    #[Test]
    public function attemptCheckAndLogout(): void
    {
        $hasher = new PasswordHasher();
        $provider = new InMemoryProvider([
            new TestUser('1', 'john', $hasher->hash('secret')),
        ]);
        $guard = new SessionGuard($provider, new ArraySessionStore(), $hasher);

        self::assertTrue($guard->attempt('john', 'secret'));
        self::assertTrue($guard->check());
        self::assertSame('1', $guard->id());

        $guard->logout();
        self::assertFalse($guard->check());
    }
}

final class InMemoryProvider implements UserProviderInterface
{
    /** @param array<int, TestUser> $users */
    public function __construct(private readonly array $users)
    {
    }

    public function findByCredentials(string $login): ?UserInterface
    {
        foreach ($this->users as $user) {
            if ($user->login === $login) {
                return $user;
            }
        }

        return null;
    }
}

final class TestUser implements UserInterface
{
    public function __construct(
        private readonly string $id,
        public readonly string $login,
        private readonly string $hash,
    ) {
    }

    public function getAuthIdentifier(): string|int
    {
        return $this->id;
    }

    public function getAuthPasswordHash(): string
    {
        return $this->hash;
    }
}
