<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Ftp;

use RuntimeException;

/**
 * Production FTP client backed by PHP's native ftp_* extension.
 */
final class NativeFtpClient implements FtpClientInterface
{
    private ?\FTP\Connection $conn = null;

    /**
      * @psalm-external-mutation-free
     */
    public function connect(string $host, int $port, int $timeout): bool
    {
        $conn = ftp_connect($host, $port, $timeout);
        if ($conn === false) {
            return false;
        }
        $this->conn = $conn;
        return true;
    }

    /**
      * @psalm-mutation-free
     */
    public function login(string $user, string $password): bool
    {
        return ftp_login($this->connection(), $user, $password);
    }

    public function pasv(bool $pasv): bool
    {
        return ftp_pasv($this->connection(), $pasv);
    }

    /**
      * @psalm-mutation-free
     */
    public function get(string $localPath, string $remotePath): bool
    {
        return ftp_get($this->connection(), $localPath, $remotePath);
    }

    /**
      * @psalm-mutation-free
     */
    public function put(string $remotePath, string $localPath): bool
    {
        return ftp_put($this->connection(), $remotePath, $localPath);
    }

    /**
      * @psalm-mutation-free
     */
    public function delete(string $remotePath): bool
    {
        return ftp_delete($this->connection(), $remotePath);
    }

    /**
      * @psalm-mutation-free
     */
    public function nlist(string $dir): bool|string
    {
        $list = ftp_nlist($this->connection(), $dir);
        if ($list === false) {
            return false;
        }
        return implode("\n", $list);
    }

    public function close(): void
    {
        if ($this->conn !== null) {
            ftp_close($this->conn);
            $this->conn = null;
        }
    }

    /**
      * @psalm-mutation-free
     */
    private function connection(): \FTP\Connection
    {
        if ($this->conn === null) {
            throw new RuntimeException('FTP: not connected.');
        }
        return $this->conn;
    }
}
