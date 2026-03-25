<?php

declare(strict_types=1);

namespace Nextphp\Auth\PasswordReset;

use Nextphp\Auth\PasswordHasher;
use Nextphp\Auth\UserProviderInterface;

/**
 * Orchestrates the password-reset flow:
 *
 *  1. sendResetLink()  — generate a token, persist it, call the notifier
 *  2. reset()          — validate token + expiry, update password, delete token
 */
final class PasswordResetService
{
    /** Default token lifetime in seconds (60 minutes). */
    private const DEFAULT_EXPIRES = 3600;

    public function __construct(
        private readonly UserProviderInterface $users,
        private readonly PasswordResetTokenStoreInterface $tokens,
        private readonly PasswordHasher $hasher = new PasswordHasher(),
        private readonly int $expires = self::DEFAULT_EXPIRES,
    ) {
    }

    /**
     * Generate a reset token and pass it to the notifier callable.
     *
     * The notifier receives (email, token) — wire up email sending there.
     * Returns false when no user exists for the given email.
     *
     * @param callable(string $email, string $token): void $notifier
     */
    public function sendResetLink(string $email, callable $notifier): bool
    {
        $user = $this->users->findByCredentials($email);
        if ($user === null) {
            return false;
        }

        $token = $this->generateToken();
        $this->tokens->store($email, $token);
        $notifier($email, $token);

        return true;
    }

    /**
     * Validate the token and reset the password.
     *
     * Returns true on success, false when:
     *   - no user found for the email
     *   - token does not exist or does not match
     *   - token has expired
     *
     * @param callable(object $user, string $newPasswordHash): void $updater
     *        Called with the user object and the new bcrypt hash so the
     *        caller controls persistence (avoids coupling to any ORM).
     */
    public function reset(string $email, string $token, string $newPassword, callable $updater): bool
    {
        $user = $this->users->findByCredentials($email);
        if ($user === null) {
            return false;
        }

        $record = $this->tokens->find($email);
        if ($record === null) {
            return false;
        }

        if (! hash_equals($record['token'], $token)) {
            return false;
        }

        if ((time() - $record['created_at']) > $this->expires) {
            $this->tokens->delete($email);
            return false;
        }

        $updater($user, $this->hasher->hash($newPassword));
        $this->tokens->delete($email);

        return true;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
