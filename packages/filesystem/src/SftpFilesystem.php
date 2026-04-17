<?php

declare(strict_types=1);

namespace Nextphp\Filesystem;

use Nextphp\Filesystem\Sftp\SftpClientInterface;
use Nextphp\Filesystem\Sftp\Ssh2SftpClient;
use RuntimeException;

final class SftpFilesystem implements FilesystemInterface
{
    private bool $connected = false;

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $host,
        private readonly string $user,
        private readonly string $password,
        private readonly int $port = 22,
        private readonly string $baseUrl = '',
        private readonly SftpClientInterface $client = new Ssh2SftpClient(),
    ) {
    }

    // -------------------------------------------------------------------------
    // FilesystemInterface
    // -------------------------------------------------------------------------

    public function put(string $path, string $contents): void
    {
        $this->ensureConnected();
        if (!$this->client->write($path, $contents)) {
            throw new RuntimeException("SFTP: failed to write '{$path}'.");
        }
    }

    public function get(string $path): string
    {
        $this->ensureConnected();
        $contents = $this->client->read($path);
        if ($contents === false) {
            throw new RuntimeException("SFTP: file not found '{$path}'.");
        }
        return $contents;
    }

    public function exists(string $path): bool
    {
        $this->ensureConnected();
        return $this->client->exists($path);
    }

    public function delete(string $path): void
    {
        $this->ensureConnected();
        $this->client->delete($path);
    }

    /**
      * @psalm-mutation-free
     */
    public function url(string $path): string
    {
        if ($this->baseUrl === '') {
            return 'sftp://' . $this->host . '/' . ltrim($path, '/');
        }
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
      * @psalm-mutation-free
     */
    public function signedUrl(string $path, int $expiresInSeconds): string
    {
        // SFTP has no native signed URL concept — return a plain URL.
        return $this->url($path);
    }

    public function readStream(string $path): mixed
    {
        $contents = $this->get($path);
        $stream   = fopen('php://temp', 'rb+');
        if ($stream === false) {
            throw new RuntimeException('SFTP: failed to create memory stream.');
        }
        fwrite($stream, $contents);
        rewind($stream);
        return $stream;
    }

    public function writeStream(string $path, mixed $stream): void
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid resource.');
        }
        $this->put($path, (string) stream_get_contents($stream));
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function ensureConnected(): void
    {
        if ($this->connected) {
            return;
        }
        if (!$this->client->connect($this->host, $this->port)) {
            throw new RuntimeException("SFTP: cannot connect to {$this->host}:{$this->port}.");
        }
        if (!$this->client->authPassword($this->user, $this->password)) {
            throw new RuntimeException('SFTP: authentication failed.');
        }
        $this->connected = true;
    }
}
