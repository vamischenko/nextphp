<?php

declare(strict_types=1);

namespace Nextphp\Auth\Jwt;

use Nextphp\Auth\UserInterface;
use Nextphp\Auth\UserProviderInterface;

/**
 * Stateless JWT guard.
 *
 * Expects the token in the Authorization: Bearer <token> header.
 * The JWT payload must contain "sub" (user identifier).
 */
final class JwtGuard
{
    /** Default token lifetime: 1 hour. */
    private const DEFAULT_TTL = 3600;

    public function __construct(
        private readonly UserProviderInterface $provider,
        private readonly JwtEncoder $encoder,
        private readonly int $ttl = self::DEFAULT_TTL,
    ) {
    }

    /**
     * Issue a signed JWT for the given user.
     */
    public function issueToken(UserInterface $user): string
    {
        return $this->encoder->encode([
            'sub' => $user->getAuthIdentifier(),
            'iat' => time(),
            'exp' => time() + $this->ttl,
        ]);
    }

    /**
     * Validate the token from the Authorization header and return the user.
     *
     * Returns null when the header is missing, the token is invalid, or
     * no user matches the subject claim.
     */
    public function fromHeader(string $authorizationHeader): ?UserInterface
    {
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authorizationHeader, 7);

        try {
            $payload = $this->encoder->decode($token);
        } catch (JwtException) {
            return null;
        }

        $sub = $payload['sub'] ?? null;
        if ($sub === null) {
            return null;
        }

        return $this->provider->findByCredentials((string) $sub);
    }
}
