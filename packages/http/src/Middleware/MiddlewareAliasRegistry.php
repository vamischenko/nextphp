<?php

declare(strict_types=1);

namespace Nextphp\Http\Middleware;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareAliasRegistry
{
    /** @var array<string, MiddlewareInterface|callable(string): MiddlewareInterface> */
    private array $aliases = [];

    public function register(string $alias, MiddlewareInterface|callable $middleware): void
    {
        $this->aliases[$alias] = $middleware;
    }

    public function has(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    public function resolve(string $alias): MiddlewareInterface
    {
        if (!isset($this->aliases[$alias])) {
            throw new InvalidArgumentException(sprintf('Middleware alias "%s" is not registered.', $alias));
        }

        $resolved = $this->aliases[$alias];
        if ($resolved instanceof MiddlewareInterface) {
            return $resolved;
        }

        return $resolved($alias);
    }
}
