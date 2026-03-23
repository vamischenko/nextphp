<?php

declare(strict_types=1);

namespace Nextphp\Auth;

use Psr\Http\Server\MiddlewareInterface;

final class PolicyMiddlewareFactory
{
    public function __construct(
        private readonly PolicyRegistry $policies,
    ) {
    }

    public function __invoke(string $alias): MiddlewareInterface
    {
        if (! str_starts_with($alias, 'can:')) {
            throw new \InvalidArgumentException('Unsupported auth middleware alias: ' . $alias);
        }

        $ability = substr($alias, 4);
        if ($ability === '') {
            throw new \InvalidArgumentException('Ability is required for can middleware.');
        }

        return new CanMiddleware($this->policies, $ability);
    }
}
