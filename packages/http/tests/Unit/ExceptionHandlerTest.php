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
        self::assertStringContainsString('<html>', (string) $response->getBody());
    }
}
