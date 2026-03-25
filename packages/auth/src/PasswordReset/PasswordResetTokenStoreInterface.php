<?php

declare(strict_types=1);

namespace Nextphp\Auth\PasswordReset;

interface PasswordResetTokenStoreInterface
{
    /**
     * Save a reset token for the given email.
     * The store must persist the creation time for expiry checks.
     */
    public function store(string $email, string $token): void;

    /**
     * Return the stored record or null if it does not exist.
     *
     * @return array{email: string, token: string, created_at: int}|null
     */
    public function find(string $email): ?array;

    /**
     * Delete all tokens for the given email.
     */
    public function delete(string $email): void;
}
