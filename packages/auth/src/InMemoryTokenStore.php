<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class InMemoryTokenStore implements TokenStoreInterface
{
    /** @var array<string, string|int> */
    private array $tokens = [];

    public function issue(string|int $userId): string
    {
        $token = bin2hex(random_bytes(20));
        $this->tokens[$token] = $userId;

        return $token;
    }

    public function resolve(string $token): string|int|null
    {
        return $this->tokens[$token] ?? null;
    }

    public function revoke(string $token): void
    {
        unset($this->tokens[$token]);
    }
}
