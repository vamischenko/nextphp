<?php

declare(strict_types=1);

namespace Nextphp\Filesystem;

final class LocalFilesystem implements FilesystemInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $root,
        private readonly string $baseUrl = '/storage',
        private readonly string $signingKey = 'nextphp-local-key',
    ) {
    }

    public function put(string $path, string $contents): void
    {
        $target = $this->fullPath($path);
        $dir = dirname($target);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Failed to create directory: ' . $dir);
        }
        file_put_contents($target, $contents);
    }

    public function get(string $path): string
    {
        $target = $this->fullPath($path);
        if (! is_file($target)) {
            throw new \RuntimeException('File not found: ' . $path);
        }

        return (string) file_get_contents($target);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->fullPath($path));
    }

    public function delete(string $path): void
    {
        $target = $this->fullPath($path);
        if (is_file($target)) {
            unlink($target);
        }
    }

    /**
      * @psalm-mutation-free
     */
    public function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public function signedUrl(string $path, int $expiresInSeconds): string
    {
        $expires = time() + max(1, $expiresInSeconds);
        $resource = ltrim($path, '/');
        $signature = hash_hmac('sha256', $resource . '|' . $expires, $this->signingKey);

        return $this->url($resource) . '?expires=' . $expires . '&signature=' . $signature;
    }

    public function readStream(string $path)
    {
        $target = $this->fullPath($path);
        $stream = fopen($target, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Failed to open file stream: ' . $path);
        }

        return $stream;
    }

    public function writeStream(string $path, $stream): void
    {
        if (! is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid resource.');
        }

        $target = $this->fullPath($path);
        $dir = dirname($target);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Failed to create directory: ' . $dir);
        }

        $targetStream = fopen($target, 'wb');
        if ($targetStream === false) {
            throw new \RuntimeException('Failed to open target file stream: ' . $path);
        }

        stream_copy_to_stream($stream, $targetStream);
        fclose($targetStream);
    }

    /**
      * @psalm-mutation-free
     */
    private function fullPath(string $path): string
    {
        return rtrim($this->root, '/') . '/' . ltrim($path, '/');
    }
}
