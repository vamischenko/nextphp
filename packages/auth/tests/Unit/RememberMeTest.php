<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\ArrayRememberMeTokenStore;
use Nextphp\Auth\RememberMeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RememberMeService::class)]
#[CoversClass(ArrayRememberMeTokenStore::class)]
final class RememberMeTest extends TestCase
{
    #[Test]
    public function createTokenAndRecallUser(): void
    {
        $store   = new ArrayRememberMeTokenStore();
        $service = new RememberMeService($store);

        $token  = $service->createToken('42');
        $userId = $service->recallUser($token);

        self::assertSame('42', $userId);
    }

    #[Test]
    public function recallReturnsNullForUnknownToken(): void
    {
        $store   = new ArrayRememberMeTokenStore();
        $service = new RememberMeService($store);

        self::assertNull($service->recallUser('no-such-token'));
    }

    #[Test]
    public function recallReturnsNullForExpiredToken(): void
    {
        $store = new ArrayRememberMeTokenStore();
        // ttl = -1 → immediately expired
        $service = new RememberMeService($store, ttl: -1);

        $token = $service->createToken('42');
        self::assertNull($service->recallUser($token));
    }

    #[Test]
    public function revokeTokenPreventsRecall(): void
    {
        $store   = new ArrayRememberMeTokenStore();
        $service = new RememberMeService($store);

        $token = $service->createToken('42');
        $service->revokeToken($token);

        self::assertNull($service->recallUser($token));
    }

    #[Test]
    public function revokeAllRemovesAllUserTokens(): void
    {
        $store   = new ArrayRememberMeTokenStore();
        $service = new RememberMeService($store);

        $t1 = $service->createToken('42');
        $t2 = $service->createToken('42');
        $service->revokeAll('42');

        self::assertNull($service->recallUser($t1));
        self::assertNull($service->recallUser($t2));
    }

    #[Test]
    public function revokeAllDoesNotAffectOtherUsers(): void
    {
        $store   = new ArrayRememberMeTokenStore();
        $service = new RememberMeService($store);

        $t1 = $service->createToken('1');
        $t2 = $service->createToken('2');
        $service->revokeAll('1');

        self::assertNull($service->recallUser($t1));
        self::assertSame('2', $service->recallUser($t2));
    }
}
