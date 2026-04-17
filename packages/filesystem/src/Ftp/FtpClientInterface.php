<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Ftp;

/**
 * Thin abstraction over PHP ftp_* functions — makes FtpFilesystem testable.
 */
/**
 * @psalm-mutable
 */
interface FtpClientInterface
{
    /**
     * @psalm-impure
     */
    public function connect(string $host, int $port, int $timeout): bool;

    /**
     * @psalm-impure
     */
    public function login(string $user, string $password): bool;

    /**
     * @psalm-impure
     */
    public function pasv(bool $pasv): bool;

    /**
     * @psalm-impure
     */
    public function get(string $localPath, string $remotePath): bool;

    /**
     * @psalm-impure
     */
    public function put(string $remotePath, string $localPath): bool;

    /**
     * @psalm-impure
     */
    public function delete(string $remotePath): bool;

    /**
     * @psalm-impure
     */
    public function nlist(string $dir): bool|string;

    /**
     * @psalm-impure
     */
    public function close(): void;
}
