<?php

declare(strict_types=1);

namespace Nextphp\Auth\PasswordReset;

/**
 * @psalm-mutable
 */
interface PasswordResetTokenStoreInterface
{
    /**
     * Save a reset token for the given email.
     * The store must persist the creation time for expiry checks.
     *
      * @psalm-external-mutation-free
     */
    public function store(string $email, string $token): void;

    /**
     * Return the stored record or null if it does not exist.
     *
     * @return array{email: string, token: string, created_at: int}|null
      * @psalm-mutation-free
     */
    public function find(string $email): ?array;

    /**
     * Delete all tokens for the given email.
     *
      * @psalm-external-mutation-free
     */
    public function delete(string $email): void;
}
