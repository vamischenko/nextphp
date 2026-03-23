<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Routing\Router;
use Nextphp\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(\Nextphp\Testing\KernelHttpTestClient::class)]
final class KernelHttpTestClientTest extends TestCase
{
    #[Test]
    public function requestsThroughHttpKernel(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): array => ['ok' => true]);
        $kernel = new HttpKernel($router);

        $client = $this->kernelClient($kernel);
        $client->get('/health')->assertStatus(200)->assertJson(['ok' => true]);
        self::assertTrue(true);
    }
}
