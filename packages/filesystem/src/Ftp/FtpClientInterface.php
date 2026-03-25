<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Ftp;

/**
 * Thin abstraction over PHP ftp_* functions — makes FtpFilesystem testable.
 */
interface FtpClientInterface
{
    public function connect(string $host, int $port, int $timeout): bool;

    public function login(string $user, string $password): bool;

    public function pasv(bool $pasv): bool;

    public function get(string $localPath, string $remotePath): bool;

    public function put(string $remotePath, string $localPath): bool;

    public function delete(string $remotePath): bool;

    public function nlist(string $dir): bool|string;

    public function close(): void;
}
