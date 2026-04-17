<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Sftp;

use RuntimeException;

/**
 * Production SFTP client backed by PHP ext-ssh2.
 *
 * @requires extension ssh2
 */
final class Ssh2SftpClient implements SftpClientInterface
{
    /** @var resource|null */
    private mixed $session = null;

    /** @var resource|null */
    private mixed $sftp = null;

    /**
      * @psalm-external-mutation-free
     */
    public function connect(string $host, int $port): bool
    {
        if (!function_exists('ssh2_connect')) {
            throw new RuntimeException('ext-ssh2 is not installed.');
        }
        $session = ssh2_connect($host, $port);
        if ($session === false) {
            return false;
        }
        $this->session = $session;
        return true;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function authPassword(string $user, string $password): bool
    {
        if (!ssh2_auth_password($this->session(), $user, $password)) {
            return false;
        }
        $sftp = ssh2_sftp($this->session());
        $this->sftp = $sftp !== false ? $sftp : null;
        return $this->sftp !== null;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function authKey(string $user, string $pubKeyFile, string $privKeyFile, string $passphrase = ''): bool
    {
        if (!ssh2_auth_pubkey_file($this->session(), $user, $pubKeyFile, $privKeyFile, $passphrase)) {
            return false;
        }
        $sftp = ssh2_sftp($this->session());
        $this->sftp = $sftp !== false ? $sftp : null;
        return $this->sftp !== null;
    }

    public function read(string $remotePath): string|false
    {
        $uri = $this->uri($remotePath);
        if (!file_exists($uri)) {
            return false;
        }
        return file_get_contents($uri);
    }

    public function write(string $remotePath, string $contents): bool
    {
        $uri    = $this->uri($remotePath);
        $dir    = dirname($remotePath);
        if ($dir !== '.' && $dir !== '/') {
            @ssh2_sftp_mkdir($this->sftpResource(), $dir, 0755, true);
        }
        return file_put_contents($uri, $contents) !== false;
    }

    /**
      * @psalm-mutation-free
     */
    public function delete(string $remotePath): bool
    {
        return ssh2_sftp_unlink($this->sftpResource(), $remotePath);
    }

    public function exists(string $remotePath): bool
    {
        return file_exists($this->uri($remotePath));
    }

    /**
      * @psalm-external-mutation-free
     */
    public function disconnect(): void
    {
        $this->sftp    = null;
        $this->session = null;
    }

    /**
      * @psalm-mutation-free
     */
    private function uri(string $path): string
    {
        return 'ssh2.sftp://' . (int) $this->sftpResource() . '/' . ltrim($path, '/');
    }

    /** @return resource */
    /**
      * @psalm-mutation-free
     */
    private function session(): mixed
    {
        if ($this->session === null) {
            throw new RuntimeException('SFTP: not connected.');
        }
        return $this->session;
    }

    /** @return resource */
    /**
      * @psalm-mutation-free
     */
    private function sftpResource(): mixed
    {
        if ($this->sftp === null) {
            throw new RuntimeException('SFTP: not authenticated.');
        }
        return $this->sftp;
    }
}
