<?php

declare(strict_types=1);

namespace Nextphp\Auth\EmailVerification;

interface EmailVerificationTokenStoreInterface
{
    public function store(string $userId, string $token): void;

    /** @return array{user_id: string, token: string, created_at: int}|null */
    public function find(string $userId): ?array;

    public function delete(string $userId): void;
}
