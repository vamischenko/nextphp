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

    /**
      * @psalm-mutation-free
     */
    public function __construct(private string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function close(): void
    {
        $this->content  = '';
        $this->position = 0;
    }

    /**
      * @psalm-pure
     */
    public function detach(): mixed
    {
        return null;
    }

    /**
      * @psalm-mutation-free
     */
    public function getSize(): int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    /**
      * @psalm-mutation-free
     */
    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    /**
      * @psalm-pure
     */
    public function isSeekable(): bool
    {
        return true;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => strlen($this->content) + $offset,
            default  => throw new \RuntimeException('Invalid whence value'),
        };
    }

    /**
      * @psalm-external-mutation-free
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
      * @psalm-pure
     */
    public function isWritable(): bool
    {
        return true;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function write(string $string): int
    {
        $before          = substr($this->content, 0, $this->position);
        $after           = substr($this->content, $this->position + strlen($string));
        $this->content   = $before . $string . $after;
        $this->position += strlen($string);

        return strlen($string);
    }

    /**
      * @psalm-pure
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function read(int $length): string
    {
        $chunk          = substr($this->content, $this->position, $length);
        $this->position += strlen($chunk);

        return $chunk;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function getContents(): string
    {
        $contents       = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $contents;
    }

    /**
      * @psalm-pure
     */
    public function getMetadata(?string $key = null): mixed
    {
        return $key !== null ? null : [];
    }
}
