<?php

declare(strict_types=1);

namespace Nextphp\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class Stream implements StreamInterface
{
    /** @var resource|null */
    private mixed $stream;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    /** @var array<string, bool> */
    private static array $readWriteHash = [
        'r' => true,
        'w+' => true,
        'r+' => true,
        'x+' => true,
        'c+' => true,
        'rb' => true,
        'w+b' => true,
        'r+b' => true,
        'x+b' => true,
        'c+b' => true,
        'rt' => true,
        'w+t' => true,
        'r+t' => true,
        'x+t' => true,
        'c+t' => true,
        'a+' => true,
    ];

    /** @var array<string, bool> */
    private static array $writeHash = [
        'w' => true,
        'w+' => true,
        'rw' => true,
        'r+' => true,
        'x+' => true,
        'c+' => true,
        'wb' => true,
        'w+b' => true,
        'r+b' => true,
        'x+b' => true,
        'c+b' => true,
        'w+t' => true,
        'r+t' => true,
        'x+t' => true,
        'c+t' => true,
        'a' => true,
        'a+' => true,
    ];

    /**
     * @param resource $stream
     */
    public function __construct(mixed $stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource.');
        }

        $this->stream = $stream;
        $meta = stream_get_meta_data($stream);
        $mode = $meta['mode'] ?? '';
        $this->seekable = (bool) $meta['seekable'];
        $this->readable = isset(self::$readWriteHash[$mode]);
        $this->writable = isset(self::$writeHash[$mode]);
    }

    public static function fromString(string $content = ''): self
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Cannot open php://temp stream.');
        }

        $instance = new self($stream);

        if ($content !== '') {
            $instance->write($content);
            $instance->rewind();
        }

        return $instance;
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->detach();
        }
    }

    public function detach(): mixed
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return $stream;
    }

    public function getSize(): ?int
    {
        if ($this->stream === null) {
            return null;
        }

        $stats = fstat($this->stream);

        return $stats !== false ? $stats['size'] : null;
    }

    public function tell(): int
    {
        $this->assertOpen();
        $position = ftell($this->stream);

        if ($position === false) {
            throw new RuntimeException('Cannot determine stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return $this->stream === null || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->assertOpen();

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek in stream.');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        $this->assertOpen();

        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable.');
        }

        $written = fwrite($this->stream, $string);

        if ($written === false) {
            throw new RuntimeException('Unable to write to stream.');
        }

        return $written;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        $this->assertOpen();

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable.');
        }

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new RuntimeException('Unable to read from stream.');
        }

        return $data;
    }

    public function getContents(): string
    {
        $this->assertOpen();

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable.');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents.');
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata(?string $key = null): mixed
    {
        if ($this->stream === null) {
            return $key !== null ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    private function assertOpen(): void
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached.');
        }
    }
}
