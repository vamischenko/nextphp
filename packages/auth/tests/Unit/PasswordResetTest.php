<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\PasswordHasher;
use Nextphp\Auth\PasswordReset\ArrayPasswordResetTokenStore;
use Nextphp\Auth\PasswordReset\DatabasePasswordResetTokenStore;
use Nextphp\Auth\PasswordReset\PasswordResetService;
use Nextphp\Auth\UserInterface;
use Nextphp\Auth\UserProviderInterface;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordResetService::class)]
#[CoversClass(ArrayPasswordResetTokenStore::class)]
#[CoversClass(DatabasePasswordResetTokenStore::class)]
final class PasswordResetTest extends TestCase
{
    // -------------------------------------------------------------------------
    // ArrayPasswordResetTokenStore
    // -------------------------------------------------------------------------

    #[Test]
    public function arrayStoreStoresAndFindsToken(): void
    {
        $store = new ArrayPasswordResetTokenStore();
        $store->store('user@example.com', 'tok123');

        $record = $store->find('user@example.com');
        self::assertNotNull($record);
        self::assertSame('tok123', $record['token']);
        self::assertSame('user@example.com', $record['email']);
    }

    #[Test]
    public function arrayStoreFindReturnsNullForUnknownEmail(): void
    {
        $store = new ArrayPasswordResetTokenStore();
        self::assertNull($store->find('nobody@example.com'));
    }

    #[Test]
    public function arrayStoreDeleteRemovesRecord(): void
    {
        $store = new ArrayPasswordResetTokenStore();
        $store->store('user@example.com', 'tok');
        $store->delete('user@example.com');

        self::assertNull($store->find('user@example.com'));
    }

    #[Test]
    public function arrayStoreOverwritesExistingToken(): void
    {
        $store = new ArrayPasswordResetTokenStore();
        $store->store('user@example.com', 'first');
        $store->store('user@example.com', 'second');

        $record = $store->find('user@example.com');
        self::assertNotNull($record);
        self::assertSame('second', $record['token']);
    }

    // -------------------------------------------------------------------------
    // DatabasePasswordResetTokenStore
    // -------------------------------------------------------------------------

    private function makeDbStore(): DatabasePasswordResetTokenStore
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE password_reset_tokens (
                email      TEXT NOT NULL PRIMARY KEY,
                token      TEXT NOT NULL,
                created_at INTEGER NOT NULL
            )',
        );

        return new DatabasePasswordResetTokenStore($pdo);
    }

    #[Test]
    public function dbStoreStoresAndFindsToken(): void
    {
        $store = $this->makeDbStore();
        $store->store('db@example.com', 'dbtoken');

        $record = $store->find('db@example.com');
        self::assertNotNull($record);
        self::assertSame('dbtoken', $record['token']);
    }

    #[Test]
    public function dbStoreFindReturnsNullForMissing(): void
    {
        $store = $this->makeDbStore();
        self::assertNull($store->find('nobody@example.com'));
    }

    #[Test]
    public function dbStoreDeleteRemovesRecord(): void
    {
        $store = $this->makeDbStore();
        $store->store('db@example.com', 'tok');
        $store->delete('db@example.com');

        self::assertNull($store->find('db@example.com'));
    }

    #[Test]
    public function dbStoreUpsertOverwritesExistingToken(): void
    {
        $store = $this->makeDbStore();
        $store->store('db@example.com', 'first');
        $store->store('db@example.com', 'second');

        $record = $store->find('db@example.com');
        self::assertNotNull($record);
        self::assertSame('second', $record['token']);
    }

    // -------------------------------------------------------------------------
    // PasswordResetService
    // -------------------------------------------------------------------------

    private function makeService(
        ?UserInterface $user = null,
        int $expires = 3600,
    ): PasswordResetService {
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('findByCredentials')->willReturn($user);

        return new PasswordResetService(
            $provider,
            new ArrayPasswordResetTokenStore(),
            new PasswordHasher(),
            $expires,
        );
    }

    #[Test]
    public function sendResetLinkReturnsFalseForUnknownUser(): void
    {
        $service = $this->makeService(user: null);
        $called  = false;

        $result = $service->sendResetLink('nobody@example.com', static function () use (&$called): void {
            $called = true;
        });

        self::assertFalse($result);
        self::assertFalse($called);
    }

    #[Test]
    public function sendResetLinkCallsNotifierWithToken(): void
    {
        $user    = $this->makeUser();
        $service = $this->makeService(user: $user);

        $notified = [];
        $result   = $service->sendResetLink('user@example.com', static function (string $e, string $t) use (&$notified): void {
            $notified = ['email' => $e, 'token' => $t];
        });

        self::assertTrue($result);
        self::assertSame('user@example.com', $notified['email']);
        self::assertNotEmpty($notified['token']);
    }

    #[Test]
    public function resetReturnsFalseForUnknownUser(): void
    {
        $service = $this->makeService(user: null);
        self::assertFalse($service->reset('nobody@example.com', 'tok', 'newpass', static fn () => null));
    }

    #[Test]
    public function resetReturnsFalseWhenNoTokenStored(): void
    {
        $service = $this->makeService(user: $this->makeUser());
        self::assertFalse($service->reset('user@example.com', 'tok', 'newpass', static fn () => null));
    }

    #[Test]
    public function resetReturnsFalseOnTokenMismatch(): void
    {
        $user    = $this->makeUser();
        $store   = new ArrayPasswordResetTokenStore();
        $store->store('user@example.com', 'correct-token');

        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('findByCredentials')->willReturn($user);

        $service = new PasswordResetService($provider, $store);

        self::assertFalse($service->reset('user@example.com', 'wrong-token', 'newpass', static fn () => null));
    }

    #[Test]
    public function resetReturnsFalseOnExpiredToken(): void
    {
        $user    = $this->makeUser();
        $store   = new ArrayPasswordResetTokenStore();
        $store->store('user@example.com', 'tok');

        // Manually set created_at far in the past via a fresh store record
        // by using expires = 0 (any token is immediately expired).
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('findByCredentials')->willReturn($user);

        $service = new PasswordResetService($provider, $store, expires: -1);

        self::assertFalse($service->reset('user@example.com', 'tok', 'newpass', static fn () => null));
    }

    #[Test]
    public function resetCallsUpdaterAndDeletesToken(): void
    {
        $user    = $this->makeUser();
        $store   = new ArrayPasswordResetTokenStore();

        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('findByCredentials')->willReturn($user);

        $service = new PasswordResetService($provider, $store);

        $captured = [];
        $service->sendResetLink('user@example.com', static function (string $_e, string $t) use (&$captured): void {
            $captured['token'] = $t;
        });

        // Send the link and capture the token
        $token   = $store->find('user@example.com')['token'] ?? '';
        $updated = null;

        $result = $service->reset(
            'user@example.com',
            $token,
            'NewSecret1!',
            static function (object $_u, string $hash) use (&$updated): void {
                $updated = $hash;
            },
        );

        self::assertTrue($result);
        self::assertNotNull($updated);
        self::assertTrue(password_verify('NewSecret1!', (string) $updated));
        // Token must be removed after successful reset
        self::assertNull($store->find('user@example.com'));
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeUser(): UserInterface
    {
        return new class implements UserInterface {
            public function getAuthIdentifier(): string|int
            {
                return 1;
            }

            public function getAuthPasswordHash(): string
            {
                return password_hash('secret', PASSWORD_DEFAULT);
            }
        };
    }
}
