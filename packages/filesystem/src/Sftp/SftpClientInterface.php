<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Sftp;

/**
 * Abstraction over SSH2/SFTP operations — makes SftpFilesystem testable
 * without requiring ext-ssh2 to be installed.
 */
/**
 * @psalm-mutable
 */
interface SftpClientInterface
{
    /**
     * @psalm-impure
     */
    public function connect(string $host, int $port): bool;

    /**
     * @psalm-impure
     */
    public function authPassword(string $user, string $password): bool;

    /**
     * @psalm-impure
     */
    public function authKey(string $user, string $pubKeyFile, string $privKeyFile, string $passphrase = ''): bool;

    /**
     * @psalm-impure
     */
    public function read(string $remotePath): string|false;

    /**
     * @psalm-impure
     */
    public function write(string $remotePath, string $contents): bool;

    /**
     * @psalm-impure
     */
    public function delete(string $remotePath): bool;

    /**
     * @psalm-impure
     */
    public function exists(string $remotePath): bool;

    /**
     * @psalm-impure
     */
    public function disconnect(): void;
}
