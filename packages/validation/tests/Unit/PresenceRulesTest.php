<?php

declare(strict_types=1);

namespace Nextphp\Validation\Tests\Unit;

use Nextphp\Validation\Presence\InMemoryPresenceVerifier;
use Nextphp\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
final class PresenceRulesTest extends TestCase
{
    #[Test]
    public function uniqueAndExistsRulesUsePresenceVerifier(): void
    {
        $presence = new InMemoryPresenceVerifier([
            'users' => [
                ['email' => 'used@example.com', 'id' => 1],
            ],
        ]);

        $validator = new Validator($presence);
        $result = $validator->validate(
            ['email' => 'used@example.com', 'user_id' => 1],
            ['email' => ['unique:users,email'], 'user_id' => ['exists:users,id']],
        );

        self::assertTrue($result->fails());
        self::assertNotEmpty($result->errorsFor('email'));
        self::assertEmpty($result->errorsFor('user_id'));
    }
}
