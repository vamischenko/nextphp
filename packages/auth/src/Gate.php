<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class Gate
{
    /** @var array<string, callable> */
    private array $abilities = [];

    public function define(string $ability, callable $callback): void
    {
        $this->abilities[$ability] = $callback;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        $callback = $this->abilities[$ability] ?? null;
        if ($callback === null) {
            return false;
        }

        return (bool) $callback(...$arguments);
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return ! $this->allows($ability, ...$arguments);
    }
}
