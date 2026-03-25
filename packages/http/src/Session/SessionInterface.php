<?php

declare(strict_types=1);

namespace Nextphp\Http\Session;

interface SessionInterface
{
    public function start(): void;

    public function getId(): string;

    public function regenerate(): void;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    /** @return array<string, mixed> */
    public function all(): array;

    public function flush(): void;

    public function save(): void;
}
