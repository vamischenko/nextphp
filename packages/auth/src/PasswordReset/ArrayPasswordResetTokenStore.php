<?php

declare(strict_types=1);

namespace Nextphp\Auth\PasswordReset;

/**
 * In-memory token store — suitable for testing and simple single-process apps.
 */
final class ArrayPasswordResetTokenStore implements PasswordResetTokenStoreInterface
{
    /** @var array<string, array{email: string, token: string, created_at: int}> */
    private array $records = [];

    /**
     * @psalm-external-mutation-free
     */
    public function store(string $email, string $token): void
    {
        $this->records[$email] = [
            'email'      => $email,
            'token'      => $token,
            'created_at' => time(),
        ];
    }

    /**
      * @psalm-mutation-free
     */
    public function find(string $email): ?array
    {
        return $this->records[$email] ?? null;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function delete(string $email): void
    {
        unset($this->records[$email]);
    }
}
