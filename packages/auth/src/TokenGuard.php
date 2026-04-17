<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class TokenGuard
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly TokenStoreInterface $store,
    ) {
    }

    public function issueFor(string|int $userId): string
    {
        return $this->store->issue($userId);
    }

    /**
     * @psalm-mutation-free
     */
    public function authenticate(string $token): string|int|null
    {
        return $this->store->resolve($token);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function revoke(string $token): void
    {
        $this->store->revoke($token);
    }
}
