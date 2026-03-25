<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Exception\ExceptionHandler;
use Nextphp\Http\Exception\NotFoundException;
use Nextphp\Http\Message\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ExceptionHandler::class)]
final class ExceptionHandlerTest extends TestCase
{
    #[Test]
    public function rendersJsonWhenAcceptIsJson(): void
    {
        $handler = new ExceptionHandler();
        $request = new ServerRequest('GET', '/x', headers: ['Accept' => 'application/json']);

        $response = $handler->handle(new NotFoundException('Not found'), $request);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('"status":404', (string) $response->getBody());
    }

    #[Test]
    public function rendersHtmlWhenNoJsonAccept(): void
    {
        $handler = new ExceptionHandler();
        $request = new ServerRequest('GET', '/x');

        $response = $handler->handle(new RuntimeException('Boom'), $request);

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('<!DOCTYPE html>', (string) $response->getBody());
    }

    #[Test]
    public function debugModeRendersTraceInHtml(): void
    {
        $handler = new ExceptionHandler(debug: true);
        $request = new ServerRequest('GET', '/x');

        $response = $handler->handle(new RuntimeException('Debug error'), $request);
        $body     = (string) $response->getBody();

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Debug error', $body);
        self::assertStringContainsString('RuntimeException', $body);
        self::assertStringContainsString('Stack Trace', $body);
    }

    #[Test]
    public function debugModeAddsTraceToJson(): void
    {
        $handler = new ExceptionHandler(debug: true);
        $request = new ServerRequest('GET', '/x', headers: ['Accept' => 'application/json']);

        $response = $handler->handle(new RuntimeException('Debug json'), $request);
        $data     = json_decode((string) $response->getBody(), true);

        self::assertArrayHasKey('trace', $data);
        self::assertArrayHasKey('class', $data);
        self::assertSame('RuntimeException', $data['class']);
    }

    #[Test]
    public function nonDebugModeDoesNotExposeTrace(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $request = new ServerRequest('GET', '/x', headers: ['Accept' => 'application/json']);

        $response = $handler->handle(new RuntimeException('Secret'), $request);
        $data     = json_decode((string) $response->getBody(), true);

        self::assertArrayNotHasKey('trace', $data);
        self::assertArrayNotHasKey('class', $data);
    }

    #[Test]
    public function debugPageShowsPreviousException(): void
    {
        $handler  = new ExceptionHandler(debug: true);
        $request  = new ServerRequest('GET', '/x');
        $previous = new \LogicException('Root cause');
        $exception = new RuntimeException('Top level', previous: $previous);

        $response = $handler->handle($exception, $request);
        $body     = (string) $response->getBody();

        self::assertStringContainsString('Root cause', $body);
        self::assertStringContainsString('LogicException', $body);
    }
}
