<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class TokenGuard
{
    public function __construct(
        private readonly TokenStoreInterface $store,
    ) {
    }

    public function issueFor(string|int $userId): string
    {
        return $this->store->issue($userId);
    }

    public function authenticate(string $token): string|int|null
    {
        return $this->store->resolve($token);
    }

    public function revoke(string $token): void
    {
        $this->store->revoke($token);
    }
}
