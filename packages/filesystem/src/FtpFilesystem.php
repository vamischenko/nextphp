<?php

declare(strict_types=1);

namespace Nextphp\Filesystem;

use Nextphp\Filesystem\Ftp\FtpClientInterface;
use Nextphp\Filesystem\Ftp\NativeFtpClient;
use RuntimeException;

final class FtpFilesystem implements FilesystemInterface
{
    private readonly FtpClientInterface $client;

    public function __construct(
        private readonly string $host,
        private readonly string $user,
        private readonly string $password,
        private readonly int $port = 21,
        private readonly int $timeout = 30,
        private readonly bool $passive = true,
        private readonly string $baseUrl = '',
        ?FtpClientInterface $client = null,
    ) {
        $this->client = $client ?? new NativeFtpClient();
    }

    // -------------------------------------------------------------------------
    // FilesystemInterface
    // -------------------------------------------------------------------------

    public function put(string $path, string $contents): void
    {
        $tmp = $this->tempFile($contents);
        try {
            $this->connected(function (FtpClientInterface $c) use ($path, $tmp): void {
                if (!$c->put($path, $tmp)) {
                    throw new RuntimeException("FTP: failed to upload '{$path}'.");
                }
            });
        } finally {
            @unlink($tmp);
        }
    }

    public function get(string $path): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ftp_');
        if ($tmp === false) {
            throw new RuntimeException('FTP: failed to create temp file.');
        }
        try {
            $this->connected(function (FtpClientInterface $c) use ($path, $tmp): void {
                if (!$c->get($tmp, $path)) {
                    throw new RuntimeException("FTP: failed to download '{$path}'.");
                }
            });
            return (string) file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    public function exists(string $path): bool
    {
        $exists = false;
        $this->connected(function (FtpClientInterface $c) use ($path, &$exists): void {
            $list = $c->nlist(dirname($path));
            if ($list !== false) {
                $exists = str_contains((string) $list, basename($path));
            }
        });
        return $exists;
    }

    public function delete(string $path): void
    {
        $this->connected(function (FtpClientInterface $c) use ($path): void {
            $c->delete($path);
        });
    }

    public function url(string $path): string
    {
        if ($this->baseUrl === '') {
            return 'ftp://' . $this->host . '/' . ltrim($path, '/');
        }
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public function signedUrl(string $path, int $expiresInSeconds): string
    {
        // FTP has no native signed URL concept — return a plain URL.
        return $this->url($path);
    }

    public function readStream(string $path): mixed
    {
        $contents = $this->get($path);
        $stream   = fopen('php://temp', 'rb+');
        if ($stream === false) {
            throw new RuntimeException('FTP: failed to create memory stream.');
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

    private function connected(callable $callback): void
    {
        if (!$this->client->connect($this->host, $this->port, $this->timeout)) {
            throw new RuntimeException("FTP: cannot connect to {$this->host}:{$this->port}.");
        }
        if (!$this->client->login($this->user, $this->password)) {
            $this->client->close();
            throw new RuntimeException('FTP: authentication failed.');
        }
        $this->client->pasv($this->passive);

        try {
            $callback($this->client);
        } finally {
            $this->client->close();
        }
    }

    private function tempFile(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ftp_');
        if ($tmp === false) {
            throw new RuntimeException('FTP: failed to create temp file.');
        }
        file_put_contents($tmp, $contents);
        return $tmp;
    }
}
