<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * Minimal PSR-7 StreamInterface backed by a plain string.
 * Used by DebugBarMiddleware to replace the response body.
 */
final class StringStream implements StreamInterface
{
    private int $position = 0;

    public function __construct(private string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
        $this->content  = '';
        $this->position = 0;
    }

    public function detach(): mixed
    {
        return null;
    }

    public function getSize(): int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => strlen($this->content) + $offset,
            default  => throw new \RuntimeException('Invalid whence value'),
        };
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $before          = substr($this->content, 0, $this->position);
        $after           = substr($this->content, $this->position + strlen($string));
        $this->content   = $before . $string . $after;
        $this->position += strlen($string);

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $chunk          = substr($this->content, $this->position, $length);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        $contents       = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return $key !== null ? null : [];
    }
}
