<?php

declare(strict_types=1);

namespace Nextphp\GraphQL\Tests\Unit;

use Nextphp\GraphQL\GraphQL;
use Nextphp\GraphQL\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphQL::class)]
#[CoversClass(Schema::class)]
final class GraphQLTest extends TestCase
{
    #[Test]
    public function executesSimpleRootQuery(): void
    {
        $schema = new Schema();
        $schema->query('health', static fn (array $args): string => 'ok');

        $graphql = new GraphQL($schema);
        $result = $graphql->execute('{ health }');

        self::assertSame(['data' => ['health' => 'ok']], $result);
    }

    #[Test]
    public function executesQueryWithArgumentsAndVariables(): void
    {
        $schema = new Schema();
        $schema->query('user', static fn (array $args): array => ['id' => $args['id'], 'name' => 'Alice']);

        $graphql = new GraphQL($schema);
        $result = $graphql->execute('{ user(id: $userId) }', ['userId' => 7]);

        self::assertSame(['data' => ['user' => ['id' => 7, 'name' => 'Alice']]], $result);
    }

    #[Test]
    public function returnsErrorsForUnknownField(): void
    {
        $graphql = new GraphQL(new Schema());
        $result = $graphql->execute('{ missing }');

        self::assertArrayHasKey('errors', $result);
    }
}
