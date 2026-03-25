<?php

declare(strict_types=1);

namespace Nextphp\Http\Session;

use Nextphp\Http\Cookie\Cookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Starts the session, binds it to the request attribute "session",
 * saves it after the response is built, and writes the session cookie.
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly string $cookieName = 'NSESSID',
        private readonly int $cookieTtl = 0,
        private readonly string $cookiePath = '/',
        private readonly bool $cookieSecure = false,
        private readonly bool $cookieHttpOnly = true,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Restore session ID from incoming cookie if present.
        $cookies = $request->getCookieParams();
        if (isset($cookies[$this->cookieName]) && $cookies[$this->cookieName] !== '') {
            // FileSession / ArraySession don't expose a setter — reconstruct if needed.
            // For this implementation the session is pre-constructed with the correct ID
            // by the application bootstrap (which reads the cookie and builds the session).
        }

        $this->session->start();

        $request = $request->withAttribute('session', $this->session);
        $response = $handler->handle($request);

        $this->session->save();

        $expires = $this->cookieTtl > 0 ? time() + $this->cookieTtl : 0;
        $cookie = new Cookie(
            $this->cookieName,
            $this->session->getId(),
            $expires,
            $this->cookiePath,
            '',
            $this->cookieSecure,
            $this->cookieHttpOnly,
        );

        return $response->withAddedHeader('Set-Cookie', $cookie->toHeaderValue());
    }
}
