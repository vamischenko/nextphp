<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class PolicyRegistry
{
    /** @var array<string, callable> */
    private array $policies = [];

    public function define(string $ability, callable $policy): void
    {
        $this->policies[$ability] = $policy;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        $policy = $this->policies[$ability] ?? null;
        if ($policy === null) {
            return false;
        }

        return (bool) $policy(...$arguments);
    }
}
