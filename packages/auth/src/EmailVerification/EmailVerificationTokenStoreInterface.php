<?php

declare(strict_types=1);

namespace Nextphp\Auth\EmailVerification;

/**
 * @psalm-mutable
 */
interface EmailVerificationTokenStoreInterface
{
    /**
     * @psalm-impure
     */
    public function store(string $userId, string $token): void;

    /**
     * @return array{user_id: string, token: string, created_at: int}|null
      * @psalm-mutation-free
     */
    public function find(string $userId): ?array;

    /**
      * @psalm-external-mutation-free
     */
    public function delete(string $userId): void;
}
