<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * @psalm-mutable
 */
interface TokenStoreInterface
{
    /**
     * @psalm-impure
     */
    public function issue(string|int $userId): string;

    /**
      * @psalm-mutation-free
     */
    public function resolve(string $token): string|int|null;

    /**
      * @psalm-external-mutation-free
     */
    public function revoke(string $token): void;
}
