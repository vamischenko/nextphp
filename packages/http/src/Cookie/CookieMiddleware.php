<?php

declare(strict_types=1);

namespace Nextphp\Http\Cookie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parses the incoming Cookie header and stores cookies as a request attribute
 * named "cookies" (array<string, string>).
 *
 * Also applies any queued outgoing cookies from the CookieJar to the response.
 */
final class CookieMiddleware implements MiddlewareInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly CookieJar $jar = new CookieJar(),
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsed = $this->parseCookieHeader($request->getHeaderLine('Cookie'));
        $request = $request->withAttribute('cookies', $parsed);

        $response = $handler->handle($request);

        return $this->jar->applyToResponse($response);
    }

    /**
     * @return array<string, string>
       * @psalm-pure
     */
    private function parseCookieHeader(string $header): array
    {
        if ($header === '') {
            return [];
        }

        $cookies = [];
        foreach (explode(';', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $eq = strpos($part, '=');
            if ($eq === false) {
                $cookies[urldecode($part)] = '';
            } else {
                $name = urldecode(substr($part, 0, $eq));
                $value = urldecode(substr($part, $eq + 1));
                $cookies[$name] = $value;
            }
        }

        return $cookies;
    }
}
