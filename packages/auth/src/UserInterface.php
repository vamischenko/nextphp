<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * @psalm-mutable
 */
interface UserInterface
{
    /**
     * @psalm-impure
     */
    public function getAuthIdentifier(): string|int;

    /**
     * @psalm-impure
     */
    public function getAuthPasswordHash(): string;
}
