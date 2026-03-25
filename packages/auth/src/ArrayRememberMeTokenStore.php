<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * In-memory remember-me token store for testing.
 */
final class ArrayRememberMeTokenStore implements RememberMeTokenStoreInterface
{
    /** @var array<string, array{user_id: string, token: string, expires_at: int}> keyed by token */
    private array $records = [];

    public function store(string $userId, string $token, int $expiresAt): void
    {
        $this->records[$token] = [
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function findByToken(string $token): ?array
    {
        return $this->records[$token] ?? null;
    }

    public function deleteByToken(string $token): void
    {
        unset($this->records[$token]);
    }

    public function deleteByUser(string $userId): void
    {
        foreach ($this->records as $token => $record) {
            if ($record['user_id'] === $userId) {
                unset($this->records[$token]);
            }
        }
    }
}
