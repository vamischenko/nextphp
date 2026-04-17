<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Flysystem;

use League\Flysystem\FilesystemOperator;
use Nextphp\Filesystem\FilesystemInterface;

final class FlysystemFilesystem implements FilesystemInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly FilesystemOperator $fs,
        private readonly string $baseUrl = '',
    ) {
    }

    public function put(string $path, string $contents): void
    {
        $this->fs->write($path, $contents);
    }

    public function get(string $path): string
    {
        return $this->fs->read($path);
    }

    public function exists(string $path): bool
    {
        return $this->fs->fileExists($path);
    }

    public function delete(string $path): void
    {
        if ($this->fs->fileExists($path)) {
            $this->fs->delete($path);
        }
    }

    /**
      * @psalm-mutation-free
     */
    public function url(string $path): string
    {
        /** @psalm-suppress UndefinedMethod */
        $publicUrl = $this->fs->publicUrl($path);

        if ($publicUrl !== '') {
            return $publicUrl;
        }

        if ($this->baseUrl !== '') {
            return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        }

        return $path;
    }

    public function signedUrl(string $path, int $expiresInSeconds): string
    {
        if (method_exists($this->fs, 'temporaryUrl')) {
            /** @psalm-suppress UndefinedMethod */
            return $this->fs->temporaryUrl($path, new \DateTimeImmutable('+' . max(1, $expiresInSeconds) . ' seconds'));
        }

        throw new \RuntimeException('Signed URLs are not supported by this Flysystem operator.');
    }

    public function readStream(string $path)
    {
        return $this->fs->readStream($path);
    }

    public function writeStream(string $path, $stream): void
    {
        if (! is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid resource.');
        }

        $this->fs->writeStream($path, $stream);
    }
}

