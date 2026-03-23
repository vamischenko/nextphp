<?php

declare(strict_types=1);

namespace Nextphp\Auth;

interface SessionStoreInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function forget(string $key): void;
}
