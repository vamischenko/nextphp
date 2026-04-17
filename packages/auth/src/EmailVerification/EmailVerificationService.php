<?php

declare(strict_types=1);

namespace Nextphp\Auth\EmailVerification;

/**
 * Handles email address verification flow:
 *  1. Generate a token and call $notifier with it (e.g. send email)
 *  2. Verify the token supplied by the user
 *  3. Mark the account as verified via $markVerified callback
 */
final class EmailVerificationService
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly EmailVerificationTokenStoreInterface $store,
        private readonly int $expiresInSeconds = 3600,
    ) {
    }

    /**
     * Generate a verification token and call the notifier.
     *
     * @param callable(string $userId, string $token): void $notifier
     */
    public function sendVerificationLink(string $userId, callable $notifier): void
    {
        $token = bin2hex(random_bytes(32));
        $this->store->store($userId, $token);
        $notifier($userId, $token);
    }

    /**
     * Verify the token and, on success, call $markVerified.
     *
     * @param callable(string $userId): void $markVerified
       * @psalm-mutation-free
     */
    public function verify(string $userId, string $token, callable $markVerified): bool
    {
        $record = $this->store->find($userId);

        if ($record === null) {
            return false;
        }

        if (!hash_equals($record['token'], $token)) {
            return false;
        }

        if ((time() - $record['created_at']) > $this->expiresInSeconds) {
            $this->store->delete($userId);
            return false;
        }

        $this->store->delete($userId);
        $markVerified($userId);

        return true;
    }
}
