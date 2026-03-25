<?php

declare(strict_types=1);

namespace Nextphp\Auth\PasswordReset;

use PDO;

/**
 * PDO-backed token store.
 *
 * Expected table DDL (any driver):
 *
 *   CREATE TABLE password_reset_tokens (
 *       email      VARCHAR(255) NOT NULL PRIMARY KEY,
 *       token      VARCHAR(255) NOT NULL,
 *       created_at INTEGER      NOT NULL
 *   );
 */
final class DatabasePasswordResetTokenStore implements PasswordResetTokenStoreInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'password_reset_tokens',
    ) {
    }

    public function store(string $email, string $token): void
    {
        // Upsert: delete then insert to stay portable across drivers.
        $this->delete($email);

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (email, token, created_at) VALUES (:email, :token, :created_at)",
        );

        $stmt->execute([
            ':email'      => $email,
            ':token'      => $token,
            ':created_at' => time(),
        ]);
    }

    public function find(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT email, token, created_at FROM {$this->table} WHERE email = :email",
        );
        $stmt->execute([':email' => $email]);

        /** @var array{email: string, token: string, created_at: string|int}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'email'      => $row['email'],
            'token'      => $row['token'],
            'created_at' => (int) $row['created_at'],
        ];
    }

    public function delete(string $email): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE email = :email",
        );
        $stmt->execute([':email' => $email]);
    }
}
