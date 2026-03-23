<?php

declare(strict_types=1);

namespace Nextphp\Console;

abstract class Command
{
    public function __construct(
        private readonly string $name,
        private readonly string $description = '',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param array<int, string> $arguments
     * @param array<string, mixed> $options
     */
    abstract public function handle(array $arguments, array $options = []): int;
}
