<?php

declare(strict_types=1);

namespace Nextphp\Http\Session;

/**
 * @psalm-mutable
 */
interface SessionInterface
{
    /**
     * @psalm-impure
     */
    public function start(): void;

    /**
     * @psalm-impure
     */
    public function getId(): string;

    /**
     * @psalm-impure
     */
    public function regenerate(): void;

    /**
     * @psalm-impure
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @psalm-impure
     */
    public function set(string $key, mixed $value): void;

    /**
     * @psalm-impure
     */
    public function has(string $key): bool;

    /**
     * @psalm-impure
     */
    public function forget(string $key): void;

    /** @return array<string, mixed> */
    /**
     * @psalm-impure
     */
    public function all(): array;

    /**
     * @psalm-impure
     */
    public function flush(): void;

    /**
     * @psalm-impure
     */
    public function save(): void;
}
