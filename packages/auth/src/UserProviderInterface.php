<?php

declare(strict_types=1);

namespace Nextphp\Auth;

interface UserProviderInterface
{
    public function findByCredentials(string $login): ?UserInterface;
}
