<?php

declare(strict_types=1);

namespace Nextphp\GraphQL\Tests\Unit;

use Nextphp\GraphQL\GraphQL;
use Nextphp\GraphQL\Http\GraphQLRouteRegistrar;
use Nextphp\GraphQL\Schema;
use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Http\Message\ServerRequest;
use Nextphp\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphQLRouteRegistrar::class)]
final class GraphQLRouteRegistrarTest extends TestCase
{
    #[Test]
    public function handlesPostGraphqlViaHttpKernelAndRouter(): void
    {
        $schema = new Schema();
        $schema->query('health', static fn (array $args): string => 'ok');
        $graphql = new GraphQL($schema);

        $router = new Router();
        (new GraphQLRouteRegistrar($graphql))->register($router);
        $kernel = new HttpKernel($router);

        $request = (new ServerRequest('POST', '/graphql'))
            ->withParsedBody(['query' => '{ health }']);

        $response = $kernel->handle($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"health":"ok"', (string) $response->getBody());
    }
}
