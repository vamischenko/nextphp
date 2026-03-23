<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use InvalidArgumentException;
use Nextphp\Http\Message\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Stream::class)]
final class StreamTest extends TestCase
{
    #[Test]
    public function createFromString(): void
    {
        $stream = Stream::fromString('hello');

        self::assertSame('hello', (string) $stream);
    }

    #[Test]
    public function createEmptyStream(): void
    {
        $stream = Stream::fromString();

        self::assertSame('', (string) $stream);
    }

    #[Test]
    public function write(): void
    {
        $stream = Stream::fromString();
        $stream->write('world');

        $stream->rewind();

        self::assertSame('world', $stream->getContents());
    }

    #[Test]
    public function readReturnsPartialContent(): void
    {
        $stream = Stream::fromString('hello world');
        $stream->rewind();

        self::assertSame('hello', $stream->read(5));
    }

    #[Test]
    public function getSize(): void
    {
        $stream = Stream::fromString('12345');

        self::assertSame(5, $stream->getSize());
    }

    #[Test]
    public function tell(): void
    {
        $stream = Stream::fromString('hello');
        $stream->seek(3);

        self::assertSame(3, $stream->tell());
    }

    #[Test]
    public function eof(): void
    {
        $stream = Stream::fromString('hi');
        $stream->rewind();
        $stream->read(10);

        self::assertTrue($stream->eof());
    }

    #[Test]
    public function isSeekable(): void
    {
        $stream = Stream::fromString('test');

        self::assertTrue($stream->isSeekable());
    }

    #[Test]
    public function close(): void
    {
        $stream = Stream::fromString('test');
        $stream->close();

        self::assertNull($stream->detach());
    }

    #[Test]
    public function detachReturnsNullForDetachedStream(): void
    {
        $stream = Stream::fromString();
        $stream->detach();

        self::assertNull($stream->detach());
    }

    #[Test]
    public function throwsOnInvalidResource(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Stream('not a resource');
    }

    #[Test]
    public function throwsOnReadFromDetachedStream(): void
    {
        $stream = Stream::fromString('test');
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $stream->read(4);
    }

    #[Test]
    public function getMetadata(): void
    {
        $stream = Stream::fromString('test');
        $meta = $stream->getMetadata();

        self::assertIsArray($meta);
        self::assertArrayHasKey('mode', $meta);
    }

    #[Test]
    public function getMetadataByKey(): void
    {
        $stream = Stream::fromString('test');

        self::assertIsString($stream->getMetadata('mode'));
    }

    #[Test]
    public function getMetadataReturnsNullForDetached(): void
    {
        $stream = Stream::fromString('test');
        $stream->detach();

        self::assertNull($stream->getMetadata('mode'));
    }
}
