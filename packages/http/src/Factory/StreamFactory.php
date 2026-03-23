<?php

declare(strict_types=1);

namespace Nextphp\Http\Factory;

use Nextphp\Http\Message\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::fromString($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = fopen($filename, $mode);

        if ($resource === false) {
            throw new RuntimeException(sprintf('Cannot open file: %s', $filename));
        }

        return new Stream($resource);
    }

    public function createStreamFromResource(mixed $resource): StreamInterface
    {
        return new Stream($resource);
    }
}
