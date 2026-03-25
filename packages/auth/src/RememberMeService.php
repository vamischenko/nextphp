<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * Manages "remember me" tokens independently of the session guard.
 *
 * Flow:
 *   - On login with remember=true: generate a token, persist it via
 *     the store, return the token to set as a cookie.
 *   - On subsequent requests: validate the cookie token via recallUser().
 *   - On logout: revoke the token.
 */
final class RememberMeService
{
    /** Default remember-me token lifetime: 30 days. */
    private const DEFAULT_TTL = 60 * 60 * 24 * 30;

    public function __construct(
        private readonly RememberMeTokenStoreInterface $store,
        private readonly int $ttl = self::DEFAULT_TTL,
    ) {
    }

    /**
     * Generate and store a remember-me token for the user.
     * Returns the raw token to be stored in a cookie.
     */
    public function createToken(string|int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->store->store((string) $userId, $token, time() + $this->ttl);

        return $token;
    }

    /**
     * Validate the cookie token and return the user ID it belongs to.
     * Returns null when the token is missing, invalid, or expired.
     */
    public function recallUser(string $token): ?string
    {
        $record = $this->store->findByToken($token);

        if ($record === null) {
            return null;
        }

        if ($record['expires_at'] < time()) {
            $this->store->deleteByToken($token);
            return null;
        }

        return $record['user_id'];
    }

    /**
     * Revoke a specific remember-me token (on logout).
     */
    public function revokeToken(string $token): void
    {
        $this->store->deleteByToken($token);
    }

    /**
     * Revoke all tokens for a user (e.g. "log out everywhere").
     */
    public function revokeAll(string|int $userId): void
    {
        $this->store->deleteByUser((string) $userId);
    }
}
