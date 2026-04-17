<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * @psalm-immutable
 */
final class PasswordHasher
{
    /**
       * @psalm-pure
     */
    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
       * @psalm-pure
     */
    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
