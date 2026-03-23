<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(\Nextphp\Testing\HttpTestClient::class)]
final class HttpTestClientTest extends TestCase
{
    #[Test]
    public function getAndPostRequests(): void
    {
        $client = $this->client(static function (string $method, string $uri, array $payload): array {
            if ($method === 'GET' && $uri === '/ping') {
                return ['status' => 200, 'json' => ['ok' => true], 'body' => 'pong'];
            }

            if ($method === 'POST' && $uri === '/users') {
                return ['status' => 201, 'json' => ['id' => 1, 'name' => $payload['name'] ?? null]];
            }

            return ['status' => 404];
        });

        $client->get('/ping')->assertStatus(200)->assertJson(['ok' => true])->assertBodyContains('pong');
        $client->post('/users', ['name' => 'Vlad'])->assertStatus(201)->assertJson(['name' => 'Vlad']);
        self::assertTrue(true);
    }
}
