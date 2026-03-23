<?php

declare(strict_types=1);

namespace Nextphp\Auth;

interface UserInterface
{
    public function getAuthIdentifier(): string|int;

    public function getAuthPasswordHash(): string;
}
