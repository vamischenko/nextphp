<?php

declare(strict_types=1);

namespace Nextphp\Http\Factory;

use Nextphp\Http\Message\ServerRequest;
use Nextphp\Http\Message\Uri;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, mixed $uri, array $serverParams = []): ServerRequestInterface
    {
        $uriObject = $uri instanceof UriInterface ? $uri : new Uri((string) $uri);

        return new ServerRequest($method, $uriObject, $serverParams);
    }
}
