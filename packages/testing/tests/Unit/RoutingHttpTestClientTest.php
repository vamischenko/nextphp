<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Http\Message\Response;
use Nextphp\Routing\Router;
use Nextphp\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(\Nextphp\Testing\RoutingHttpTestClient::class)]
final class RoutingHttpTestClientTest extends TestCase
{
    #[Test]
    public function dispatchesRouterHandlers(): void
    {
        $router = new Router();
        $router->get('/ping', static fn (): string => 'pong');
        $router->get('/users/{id}', static fn (string $id): array => ['id' => $id]);
        $router->get('/raw', static fn (ServerRequestInterface $request): Response => new Response(200, body: 'ok:' . $request->getMethod()));

        $client = $this->routingClient($router);

        $client->get('/ping')->assertStatus(200)->assertBodyContains('pong');
        $client->get('/users/7')->assertStatus(200)->assertJson(['id' => '7']);
        $client->get('/raw')->assertStatus(200)->assertBodyContains('ok:GET');
        self::assertTrue(true);
    }
}
