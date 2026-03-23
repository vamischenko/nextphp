<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Http\Message\ServerRequest;
use Nextphp\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(HttpKernel::class)]
final class HttpKernelTest extends TestCase
{
    #[Test]
    public function handlesRouteAndAppliesAliasMiddleware(): void
    {
        $router = new Router();
        $router->get('/ping', fn (): string => 'pong')->middleware(['tag']);

        $kernel = new HttpKernel($router);
        $kernel->aliases()->register('tag', new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);
                $body = (string) $response->getBody() . ':mw';

                return $response->withBody(\Nextphp\Http\Message\Stream::fromString($body));
            }
        });

        $response = $kernel->handle(new ServerRequest('GET', '/ping'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('pong:mw', (string) $response->getBody());
    }

    #[Test]
    public function convertsRouteNotFoundToHttpResponse(): void
    {
        $router = new Router();
        $kernel = new HttpKernel($router);

        $response = $kernel->handle(new ServerRequest('GET', '/missing'));
        self::assertSame(404, $response->getStatusCode());
    }
}
