<?php

declare(strict_types=1);

namespace Nextphp\Auth;

/**
 * @psalm-mutable
 */
interface SessionStoreInterface
{
    /**
      * @psalm-mutation-free
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
      * @psalm-external-mutation-free
     */
    public function put(string $key, mixed $value): void;

    /**
      * @psalm-external-mutation-free
     */
    public function forget(string $key): void;
}
