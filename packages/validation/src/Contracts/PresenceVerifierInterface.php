<?php

declare(strict_types=1);

namespace Nextphp\Validation\Contracts;

interface PresenceVerifierInterface
{
    public function exists(string $table, string $column, mixed $value): bool;

    public function unique(string $table, string $column, mixed $value): bool;
}
