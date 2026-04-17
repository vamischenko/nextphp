<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class ArraySessionStore implements SessionStoreInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
      * @psalm-mutation-free
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }
}
