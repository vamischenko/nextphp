<?php

declare(strict_types=1);

namespace Nextphp\Auth;

interface RememberMeTokenStoreInterface
{
    /**
     * Persist a remember-me token for a user.
     */
    public function store(string $userId, string $token, int $expiresAt): void;

    /**
     * Find the record for a given token.
     *
     * @return array{user_id: string, token: string, expires_at: int}|null
     */
    public function findByToken(string $token): ?array;

    /**
     * Delete a specific token.
     */
    public function deleteByToken(string $token): void;

    /**
     * Delete all tokens for a user.
     */
    public function deleteByUser(string $userId): void;
}
