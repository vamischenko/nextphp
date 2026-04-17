<?php

declare(strict_types=1);

namespace Nextphp\GraphQL;

/**
 * @psalm-immutable
 */
final class Schema
{
    /** @var array<string, callable(array<string, mixed>): mixed> */
    private array $queries = [];

    /**
     * @param callable(array<string, mixed>): mixed $resolver
     */
    public function query(string $field, callable $resolver): void
    {
        $this->queries[$field] = $resolver;
    }

    /**
     * @return callable(array<string, mixed>): mixed
     */
    public function resolverFor(string $field): callable
    {
        if (! isset($this->queries[$field])) {
            throw new \InvalidArgumentException(sprintf('Unknown query field "%s".', $field));
        }

        return $this->queries[$field];
    }
}
