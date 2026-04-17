<?php

declare(strict_types=1);

namespace Nextphp\Http\Factory;

use Nextphp\Http\Message\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

final class UriFactory implements UriFactoryInterface
{
    /**
      * @psalm-pure
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
