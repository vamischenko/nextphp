<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * @psalm-mutable
 */
interface RememberMeTokenStoreInterface
{
    /**
     * Persist a remember-me token for a user.
     *
      * @psalm-external-mutation-free
     */
    public function store(string $userId, string $token, int $expiresAt): void;

    /**
     * Find the record for a given token.
     *
     * @return array{user_id: string, token: string, expires_at: int}|null
      * @psalm-mutation-free
     */
    public function findByToken(string $token): ?array;

    /**
     * Delete a specific token.
     *
      * @psalm-external-mutation-free
     */
    public function deleteByToken(string $token): void;

    /**
     * Delete all tokens for a user.
     *
      * @psalm-external-mutation-free
     */
    public function deleteByUser(string $userId): void;
}
