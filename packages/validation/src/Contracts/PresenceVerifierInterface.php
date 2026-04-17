<?php

declare(strict_types=1);

namespace Nextphp\Validation\Contracts;

/**
 * @psalm-mutable
 */
interface PresenceVerifierInterface
{
    /**
     * @psalm-impure
     */
    public function exists(string $table, string $column, mixed $value): bool;

    /**
     * @psalm-impure
     */
    public function unique(string $table, string $column, mixed $value): bool;
}
