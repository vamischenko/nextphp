<?php

declare(strict_types=1);

namespace Nextphp\Octane\Tests\Unit;

use Nextphp\Core\Container\Container;
use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Http\Message\ServerRequest;
use Nextphp\Octane\Lifecycle\OctaneLifecycleHooks;
use Nextphp\Octane\Worker\OctaneHttpKernelBridge;
use Nextphp\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Nextphp\Octane\Worker\OctaneWorker::class)]
#[CoversClass(OctaneHttpKernelBridge::class)]
#[CoversClass(OctaneLifecycleHooks::class)]
final class OctaneWorkerTest extends TestCase
{
    #[Test]
    public function handlesRequestAndFiresLifecycleHooks(): void
    {
        $router = new Router();
        $router->get('/ping', fn (): string => 'pong');
        $kernel = new HttpKernel($router);
        $container = new Container();

        $bridge = new OctaneHttpKernelBridge($container);
        $worker = $bridge->worker($kernel);
        $events = [];

        $worker->hooks()->onWorkerStart(function () use (&$events): void {
            $events[] = 'start';
        });
        $worker->hooks()->onRequestStart(function () use (&$events): void {
            $events[] = 'req:start';
        });
        $worker->hooks()->onRequestEnd(function () use (&$events): void {
            $events[] = 'req:end';
        });
        $worker->hooks()->onWorkerStop(function () use (&$events): void {
            $events[] = 'stop';
        });

        $worker->boot();
        $response = $worker->handle(new ServerRequest('GET', '/ping'));
        $worker->stop();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['start', 'req:start', 'req:end', 'stop'], $events);
    }
}
