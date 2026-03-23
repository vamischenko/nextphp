<?php

declare(strict_types=1);

namespace Nextphp\Http\Factory;

use Nextphp\Http\Message\Request;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

final class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, mixed $uri): RequestInterface
    {
        return new Request($method, $uri instanceof UriInterface ? $uri : (string) $uri);
    }
}
