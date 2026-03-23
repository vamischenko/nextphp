<?php

declare(strict_types=1);

namespace Nextphp\Auth;

interface TokenStoreInterface
{
    public function issue(string|int $userId): string;

    public function resolve(string $token): string|int|null;

    public function revoke(string $token): void;
}
