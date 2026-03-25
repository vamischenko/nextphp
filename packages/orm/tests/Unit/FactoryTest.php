<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Unit;

use Nextphp\Orm\Factory\FakerGenerator;
use Nextphp\Orm\Factory\ModelFactory;
use Nextphp\Orm\Model\Model;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ModelFactory::class)]
#[CoversClass(FakerGenerator::class)]
final class FactoryTest extends TestCase
{
    #[Test]
    public function makeRawReturnsSingleArray(): void
    {
        $attrs = StubUserFactory::new()->makeRaw();
        self::assertIsArray($attrs);
        self::assertArrayHasKey('name', $attrs);
        self::assertArrayHasKey('email', $attrs);
    }

    #[Test]
    public function makeRawWithCount(): void
    {
        $result = StubUserFactory::new()->count(3)->makeRaw();
        self::assertIsArray($result);
        self::assertCount(3, $result);
    }

    #[Test]
    public function stateOverridesDefinition(): void
    {
        $attrs = StubUserFactory::new()->state(['name' => 'Override'])->makeRaw();
        self::assertSame('Override', $attrs['name']);
    }

    #[Test]
    public function makeRawWithOverrideArg(): void
    {
        $attrs = StubUserFactory::new()->makeRaw(['email' => 'custom@test.com']);
        self::assertSame('custom@test.com', $attrs['email']);
    }

    #[Test]
    public function makeBuildsSingleModel(): void
    {
        $model = StubUserFactory::new()->make();
        self::assertInstanceOf(StubUser::class, $model);
        self::assertNotEmpty($model->getAttribute('name'));
    }

    #[Test]
    public function makeBuildsMultipleModels(): void
    {
        $models = StubUserFactory::new()->count(5)->make();
        self::assertIsArray($models);
        self::assertCount(5, $models);
        foreach ($models as $m) {
            self::assertInstanceOf(StubUser::class, $m);
        }
    }

    #[Test]
    public function afterMakingCallbackIsApplied(): void
    {
        $attrs = StubUserFactory::new()
            ->afterMaking(static fn(array $a): array => array_merge($a, ['extra' => 'yes']))
            ->makeRaw();

        self::assertSame('yes', $attrs['extra']);
    }

    #[Test]
    public function fakerGeneratorProducesUniqueEmails(): void
    {
        $faker  = new FakerGenerator();
        $email1 = $faker->email();
        $email2 = $faker->email();
        self::assertNotSame($email1, $email2);
        self::assertStringContainsString('@example.com', $email1);
    }

    #[Test]
    public function fakerName(): void
    {
        $faker = new FakerGenerator();
        self::assertStringContainsString(' ', $faker->name());
    }

    #[Test]
    public function fakerUuid(): void
    {
        $faker = new FakerGenerator();
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $faker->uuid(),
        );
    }

    #[Test]
    public function fakerBoolean(): void
    {
        $faker = new FakerGenerator();
        $true  = $faker->boolean(100);
        $false = $faker->boolean(0);
        self::assertTrue($true);
        self::assertFalse($false);
    }
}

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

final class StubUser extends Model
{
    protected string $table = 'users';
    /** @var list<string> */
    protected array $fillable = ['name', 'email', 'role'];
}

/**
 * @extends ModelFactory<StubUser>
 */
final class StubUserFactory extends ModelFactory
{
    protected string $model = StubUser::class;

    public function definition(): array
    {
        return [
            'name'  => $this->faker->name(),
            'email' => $this->faker->email(),
            'role'  => 'user',
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }
}
