<?php

declare(strict_types=1);

namespace Nextphp\Log\Tests\Unit;

use Nextphp\Log\Handler\ArrayHandler;
use Nextphp\Log\Handler\NullHandler;
use Nextphp\Log\Handler\StreamHandler;
use Nextphp\Log\LogLevel;
use Nextphp\Log\Logger;
use Nextphp\Log\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Logger::class)]
#[CoversClass(ArrayHandler::class)]
#[CoversClass(NullHandler::class)]
#[CoversClass(StreamHandler::class)]
#[CoversClass(LogLevel::class)]
#[CoversClass(LogRecord::class)]
final class LoggerTest extends TestCase
{
    #[Test]
    public function logRecordIsPassedToHandler(): void
    {
        $handler = new ArrayHandler();
        $logger  = (new Logger())->pushHandler($handler);

        $logger->info('Hello world');

        self::assertCount(1, $handler->records());
        self::assertSame('Hello world', $handler->records()[0]->message);
        self::assertSame(LogLevel::Info, $handler->records()[0]->level);
    }

    #[Test]
    public function contextInterpolation(): void
    {
        $handler = new ArrayHandler();
        $logger  = (new Logger())->pushHandler($handler);

        $logger->error('User {id} failed', ['id' => 42]);

        self::assertSame('User 42 failed', $handler->records()[0]->message);
    }

    #[Test]
    public function minLevelFiltersLowMessages(): void
    {
        $handler = new ArrayHandler();
        $logger  = (new Logger(LogLevel::Warning))->pushHandler($handler);

        $logger->debug('debug message');
        $logger->info('info message');
        $logger->warning('warning message');
        $logger->error('error message');

        self::assertCount(2, $handler->records());
        self::assertSame(LogLevel::Warning, $handler->records()[0]->level);
        self::assertSame(LogLevel::Error, $handler->records()[1]->level);
    }

    #[Test]
    public function allLevelsAreDispatched(): void
    {
        $handler = new ArrayHandler();
        $logger  = (new Logger())->pushHandler($handler);

        $logger->emergency('em');
        $logger->alert('al');
        $logger->critical('cr');
        $logger->error('er');
        $logger->warning('wa');
        $logger->notice('no');
        $logger->info('in');
        $logger->debug('de');

        self::assertCount(8, $handler->records());
    }

    #[Test]
    public function multipleHandlersReceiveSameRecord(): void
    {
        $h1 = new ArrayHandler();
        $h2 = new ArrayHandler();
        $logger = (new Logger())->pushHandler($h1)->pushHandler($h2);

        $logger->info('broadcast');

        self::assertCount(1, $h1->records());
        self::assertCount(1, $h2->records());
    }

    #[Test]
    public function nullHandlerDiscardsEverything(): void
    {
        $handler = new NullHandler();
        $logger  = (new Logger())->pushHandler($handler);

        $logger->emergency('loud');
        // no assertion needed — just must not throw
        self::assertTrue(true);
    }

    #[Test]
    public function streamHandlerWritesToResource(): void
    {
        $resource = fopen('php://memory', 'wb+');
        self::assertNotFalse($resource);

        $handler = new StreamHandler($resource);
        $logger  = (new Logger())->pushHandler($handler);

        $logger->info('stream test');

        rewind($resource);
        $content = stream_get_contents($resource);
        fclose($resource);

        self::assertStringContainsString('INFO', $content);
        self::assertStringContainsString('stream test', $content);
    }

    #[Test]
    public function streamHandlerRespectsMinLevel(): void
    {
        $resource = fopen('php://memory', 'wb+');
        self::assertNotFalse($resource);

        $handler = new StreamHandler($resource, LogLevel::Error);
        $logger  = (new Logger())->pushHandler($handler);

        $logger->debug('ignored');
        $logger->error('shown');

        rewind($resource);
        $content = stream_get_contents($resource);
        fclose($resource);

        self::assertStringNotContainsString('ignored', $content);
        self::assertStringContainsString('shown', $content);
    }

    #[Test]
    public function arrayHandlerCanBeCleared(): void
    {
        $handler = new ArrayHandler();
        $logger  = (new Logger())->pushHandler($handler);

        $logger->info('first');
        $handler->clear();
        $logger->info('second');

        self::assertCount(1, $handler->records());
        self::assertSame('second', $handler->records()[0]->message);
    }

    #[Test]
    public function contextWithVariousTypes(): void
    {
        $handler = new ArrayHandler();
        $logger  = (new Logger())->pushHandler($handler);

        $logger->info('{bool} {null} {arr}', [
            'bool' => true,
            'null' => null,
            'arr'  => ['a', 'b'],
        ]);

        $msg = $handler->records()[0]->message;
        self::assertStringContainsString('true', $msg);
        self::assertStringContainsString('null', $msg);
        self::assertStringContainsString('"a"', $msg);
    }
}
