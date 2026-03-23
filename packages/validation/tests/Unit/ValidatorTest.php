<?php

declare(strict_types=1);

namespace Nextphp\Validation\Tests\Unit;

use Nextphp\Validation\ValidationRuleInterface;
use Nextphp\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    #[Test]
    public function passesWithValidData(): void
    {
        $validator = new Validator();
        $result = $validator->validate(
            ['email' => 'team@nextphp.dev', 'name' => 'Nextphp'],
            ['email' => ['required', 'email'], 'name' => ['required', 'min:3', 'max:20']],
        );

        self::assertTrue($result->passes());
        self::assertSame([], $result->errors());
    }

    #[Test]
    public function failsWithInvalidData(): void
    {
        $validator = new Validator();
        $result = $validator->validate(
            ['email' => 'broken-email', 'name' => 'ab'],
            ['email' => ['required', 'email'], 'name' => ['required', 'min:3']],
        );

        self::assertTrue($result->fails());
        self::assertNotEmpty($result->errorsFor('email'));
        self::assertNotEmpty($result->errorsFor('name'));
    }

    #[Test]
    public function supportsCustomRuleObject(): void
    {
        $validator = new Validator();
        $result = $validator->validate(
            ['username' => 'admin'],
            ['username' => [new NotAdminRule()]],
        );

        self::assertTrue($result->fails());
        self::assertSame(['Username cannot be admin.'], $result->errorsFor('username'));
    }
}

final class NotAdminRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if ($value === 'admin') {
            return 'Username cannot be admin.';
        }

        return null;
    }
}
