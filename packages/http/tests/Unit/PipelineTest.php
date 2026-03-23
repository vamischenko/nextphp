<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Handler\CallableHandler;
use Nextphp\Http\Message\Response;
use Nextphp\Http\Message\ServerRequest;
use Nextphp\Http\Middleware\Pipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

#[CoversClass(Pipeline::class)]
#[CoversClass(CallableHandler::class)]
final class PipelineTest extends TestCase
{
    #[Test]
    public function executesMiddlewareInOrder(): void
    {
        $log = [];

        $mw1 = new class ($log, 'first') implements MiddlewareInterface {
            public function __construct(private array &$log, private string $name)
            {
            }

            public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = $this->name . ':before';
                $response = $handler->handle($req);
                $this->log[] = $this->name . ':after';

                return $response;
            }
        };

        $mw2 = new class ($log, 'second') implements MiddlewareInterface {
            public function __construct(private array &$log, private string $name)
            {
            }

            public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = $this->name . ':before';
                $response = $handler->handle($req);
                $this->log[] = $this->name . ':after';

                return $response;
            }
        };

        $fallback = new CallableHandler(fn () => new Response(200));
        $pipeline = (new Pipeline($fallback))->pipe($mw1)->pipe($mw2);

        $request = new ServerRequest('GET', 'http://example.com');
        $pipeline->handle($request);

        self::assertSame(['first:before', 'second:before', 'second:after', 'first:after'], $log);
    }

    #[Test]
    public function throwsWithoutFallbackWhenExhausted(): void
    {
        $pipeline = new Pipeline();

        $this->expectException(RuntimeException::class);

        $pipeline->handle(new ServerRequest('GET', 'http://example.com'));
    }

    #[Test]
    public function middlewareCanShortCircuit(): void
    {
        $shortCircuit = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response(401);
            }
        };

        $fallback = new CallableHandler(fn () => new Response(200));
        $pipeline = (new Pipeline($fallback))->pipe($shortCircuit);

        $response = $pipeline->handle(new ServerRequest('GET', 'http://example.com'));

        self::assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function middlewareCanModifyRequest(): void
    {
        $received = null;

        $modifier = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($req->withAttribute('modified', true));
            }
        };

        $fallback = new CallableHandler(function (ServerRequestInterface $req) use (&$received) {
            $received = $req->getAttribute('modified');

            return new Response(200);
        });

        $pipeline = (new Pipeline($fallback))->pipe($modifier);
        $pipeline->handle(new ServerRequest('GET', 'http://example.com'));

        self::assertTrue($received);
    }

    #[Test]
    public function pipeIsImmutable(): void
    {
        $pipeline = new Pipeline();
        $mw = new class () implements MiddlewareInterface {
            public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($req);
            }
        };

        $piped = $pipeline->pipe($mw);

        self::assertNotSame($pipeline, $piped);
    }
}
