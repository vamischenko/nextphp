<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\EmailVerification\ArrayEmailVerificationTokenStore;
use Nextphp\Auth\EmailVerification\EmailVerificationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailVerificationService::class)]
#[CoversClass(ArrayEmailVerificationTokenStore::class)]
final class EmailVerificationTest extends TestCase
{
    private ArrayEmailVerificationTokenStore $store;
    private EmailVerificationService $service;

    protected function setUp(): void
    {
        $this->store   = new ArrayEmailVerificationTokenStore();
        $this->service = new EmailVerificationService($this->store);
    }

    #[Test]
    public function sendVerificationLinkCallsNotifier(): void
    {
        $notified = null;

        $this->service->sendVerificationLink('user1', function (string $userId, string $token) use (&$notified): void {
            $notified = ['userId' => $userId, 'token' => $token];
        });

        self::assertNotNull($notified);
        self::assertSame('user1', $notified['userId']);
        self::assertNotEmpty($notified['token']);
    }

    #[Test]
    public function verifyWithCorrectTokenSucceeds(): void
    {
        $capturedToken = '';
        $verified      = false;

        $this->service->sendVerificationLink('user2', function (string $userId, string $token) use (&$capturedToken): void {
            $capturedToken = $token;
        });

        $result = $this->service->verify('user2', $capturedToken, function () use (&$verified): void {
            $verified = true;
        });

        self::assertTrue($result);
        self::assertTrue($verified);
    }

    #[Test]
    public function verifyDeletesTokenAfterSuccess(): void
    {
        $capturedToken = '';

        $this->service->sendVerificationLink('user3', function (string $userId, string $token) use (&$capturedToken): void {
            $capturedToken = $token;
        });

        $this->service->verify('user3', $capturedToken, function (): void {});

        // Second verify with same token fails
        $result = $this->service->verify('user3', $capturedToken, function (): void {});
        self::assertFalse($result);
    }

    #[Test]
    public function verifyWithWrongTokenFails(): void
    {
        $this->service->sendVerificationLink('user4', function (): void {});

        $result = $this->service->verify('user4', 'wrong-token', function (): void {});
        self::assertFalse($result);
    }

    #[Test]
    public function verifyWithExpiredTokenFails(): void
    {
        $service = new EmailVerificationService($this->store, expiresInSeconds: -1);
        $token   = '';

        $service->sendVerificationLink('user5', function (string $userId, string $t) use (&$token): void {
            $token = $t;
        });

        $result = $service->verify('user5', $token, function (): void {});
        self::assertFalse($result);
    }

    #[Test]
    public function verifyForUnknownUserFails(): void
    {
        $result = $this->service->verify('nobody', 'any-token', function (): void {});
        self::assertFalse($result);
    }
}
