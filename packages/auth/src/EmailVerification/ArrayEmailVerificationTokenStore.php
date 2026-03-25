<?php

declare(strict_types=1);

namespace Nextphp\Auth\EmailVerification;

final class ArrayEmailVerificationTokenStore implements EmailVerificationTokenStoreInterface
{
    /** @var array<string, array{user_id: string, token: string, created_at: int}> */
    private array $store = [];

    public function store(string $userId, string $token): void
    {
        $this->store[$userId] = [
            'user_id'    => $userId,
            'token'      => $token,
            'created_at' => time(),
        ];
    }

    public function find(string $userId): ?array
    {
        return $this->store[$userId] ?? null;
    }

    public function delete(string $userId): void
    {
        unset($this->store[$userId]);
    }
}
