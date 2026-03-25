<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Sftp;

/**
 * Abstraction over SSH2/SFTP operations — makes SftpFilesystem testable
 * without requiring ext-ssh2 to be installed.
 */
interface SftpClientInterface
{
    public function connect(string $host, int $port): bool;

    public function authPassword(string $user, string $password): bool;

    public function authKey(string $user, string $pubKeyFile, string $privKeyFile, string $passphrase = ''): bool;

    public function read(string $remotePath): string|false;

    public function write(string $remotePath, string $contents): bool;

    public function delete(string $remotePath): bool;

    public function exists(string $remotePath): bool;

    public function disconnect(): void;
}
