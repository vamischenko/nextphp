<?php

declare(strict_types=1);

namespace Nextphp\Http\Session;

/**
 * In-memory session — suitable for testing and stateless contexts.
 */
final class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    private string $id;

    private bool $started = false;

    public function __construct(string $id = '')
    {
        $this->id = $id !== '' ? $id : $this->newId();
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->started = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function regenerate(): void
    {
        $this->id = $this->newId();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function flush(): void
    {
        $this->data = [];
    }

    public function save(): void
    {
        // no-op for in-memory
    }

    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
