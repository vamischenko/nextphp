<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * @psalm-mutable
 */
interface UserProviderInterface
{
    /**
     * @psalm-impure
     */
    public function findByCredentials(string $login): ?UserInterface;
}
