<?php

declare(strict_types=1);

namespace Nextphp\Validation\Tests\Unit;

use Nextphp\Validation\Attribute\Email;
use Nextphp\Validation\Attribute\Max;
use Nextphp\Validation\Attribute\Min;
use Nextphp\Validation\Attribute\Required;
use Nextphp\Validation\AttributeValidator;
use Nextphp\Validation\Rule\ClosureRule;
use Nextphp\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClosureRule::class)]
#[CoversClass(AttributeValidator::class)]
final class ClosureAndAttributeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Closure rules
    // -------------------------------------------------------------------------

    #[Test]
    public function closureRulePassesWhenCallbackReturnsNull(): void
    {
        $result = (new Validator())->validate(
            ['age' => 20],
            ['age' => [static fn (string $f, mixed $v): ?string => null]],
        );

        self::assertTrue($result->passes());
    }

    #[Test]
    public function closureRuleFailsWhenCallbackReturnsMessage(): void
    {
        $result = (new Validator())->validate(
            ['age' => 15],
            ['age' => [static fn (string $f, mixed $v): ?string => (int) $v >= 18 ? null : "$f must be 18+"]],
        );

        self::assertFalse($result->passes());
        self::assertSame('age must be 18+', $result->errors()['age'][0]);
    }

    #[Test]
    public function closureRuleReceivesFullData(): void
    {
        $received = [];
        (new Validator())->validate(
            ['a' => 1, 'b' => 2],
            ['a' => [static function (string $f, mixed $v, array $data) use (&$received): ?string {
                $received = $data;
                return null;
            }]],
        );

        self::assertSame(['a' => 1, 'b' => 2], $received);
    }

    #[Test]
    public function closureRuleCanBeUsedAlongsideStringRules(): void
    {
        $result = (new Validator())->validate(
            ['name' => 'Jo'],
            [
                'name' => [
                    'required',
                    static fn (string $f, mixed $v): ?string => strlen((string) $v) >= 3 ? null : "$f too short",
                ],
            ],
        );

        self::assertFalse($result->passes());
        self::assertStringContainsString('too short', $result->errors()['name'][0]);
    }

    // -------------------------------------------------------------------------
    // Attribute-based validation
    // -------------------------------------------------------------------------

    #[Test]
    public function attributeValidatorPassesForValidDto(): void
    {
        $dto        = new class {
            #[Required]
            #[Email]
            public string $email = 'user@example.com';

            #[Required]
            #[Min(3)]
            #[Max(50)]
            public string $name = 'Alice';
        };

        $result = AttributeValidator::validate($dto);
        self::assertTrue($result->passes());
    }

    #[Test]
    public function attributeValidatorFailsForMissingRequired(): void
    {
        $dto = new class {
            #[Required]
            public string $email = '';
        };

        $result = AttributeValidator::validate($dto);
        self::assertFalse($result->passes());
        self::assertArrayHasKey('email', $result->errors());
    }

    #[Test]
    public function attributeValidatorFailsForInvalidEmail(): void
    {
        $dto = new class {
            #[Required]
            #[Email]
            public string $email = 'not-an-email';
        };

        $result = AttributeValidator::validate($dto);
        self::assertFalse($result->passes());
        self::assertArrayHasKey('email', $result->errors());
    }

    #[Test]
    public function attributeValidatorFailsForMinViolation(): void
    {
        $dto = new class {
            #[Required]
            #[Min(5)]
            public string $name = 'Jo';
        };

        $result = AttributeValidator::validate($dto);
        self::assertFalse($result->passes());
        self::assertArrayHasKey('name', $result->errors());
    }

    #[Test]
    public function attributeValidatorFailsForMaxViolation(): void
    {
        $dto = new class {
            #[Max(3)]
            public string $tag = 'toolong';
        };

        $result = AttributeValidator::validate($dto);
        self::assertFalse($result->passes());
        self::assertArrayHasKey('tag', $result->errors());
    }

    #[Test]
    public function attributeValidatorIgnoresPropertiesWithNoAttributes(): void
    {
        $dto = new class {
            public string $anything = '';
        };

        $result = AttributeValidator::validate($dto);
        self::assertTrue($result->passes());
    }
}
