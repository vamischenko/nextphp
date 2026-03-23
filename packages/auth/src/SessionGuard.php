<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class SessionGuard
{
    private const SESSION_KEY = '_nextphp_auth_user';

    public function __construct(
        private readonly UserProviderInterface $provider,
        private readonly SessionStoreInterface $session,
        private readonly PasswordHasher $hasher = new PasswordHasher(),
    ) {
    }

    public function attempt(string $login, string $password): bool
    {
        $user = $this->provider->findByCredentials($login);
        if ($user === null) {
            return false;
        }

        if (! $this->hasher->verify($password, $user->getAuthPasswordHash())) {
            return false;
        }

        $this->session->put(self::SESSION_KEY, (string) $user->getAuthIdentifier());

        return true;
    }

    public function check(): bool
    {
        return $this->session->get(self::SESSION_KEY) !== null;
    }

    public function id(): string|int|null
    {
        return $this->session->get(self::SESSION_KEY);
    }

    public function logout(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function userId(): string|int|null
    {
        return $this->id();
    }
}
