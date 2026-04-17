<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Flysystem;

use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Nextphp\Filesystem\FilesystemInterface;

/**
 * Adapter to expose Nextphp FilesystemInterface as Flysystem adapter.
 *
 * Scope: files only (no directory listing/metadata guarantees).
 */
final class NextphpFilesystemAdapter implements FilesystemAdapter
{
    private PathPrefixer $prefixer;

    public function __construct(
        private readonly FilesystemInterface $fs,
        string $prefix = '',
    ) {
        $this->prefixer = new PathPrefixer($prefix);
    }

    public function fileExists(string $location): bool
    {
        return $this->fs->exists($this->prefixer->prefixPath($location));
    }

    /**
      * @psalm-pure
     */
    public function directoryExists(string $location): bool
    {
        return false;
    }

    public function write(string $location, string $contents, Config $config): void
    {
        try {
            $this->fs->put($this->prefixer->prefixPath($location), $contents);
        } catch (\Throwable $e) {
            throw UnableToWriteFile::atLocation($location, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $location, $contents, Config $config): void
    {
        try {
            $this->fs->writeStream($this->prefixer->prefixPath($location), $contents);
        } catch (\Throwable $e) {
            throw UnableToWriteFile::atLocation($location, $e->getMessage(), $e);
        }
    }

    public function read(string $location): string
    {
        try {
            return $this->fs->get($this->prefixer->prefixPath($location));
        } catch (\Throwable $e) {
            throw UnableToReadFile::fromLocation($location, $e->getMessage(), $e);
        }
    }

    public function readStream(string $location)
    {
        try {
            return $this->fs->readStream($this->prefixer->prefixPath($location));
        } catch (\Throwable $e) {
            throw UnableToReadFile::fromLocation($location, $e->getMessage(), $e);
        }
    }

    public function delete(string $location): void
    {
        $this->fs->delete($this->prefixer->prefixPath($location));
    }

    /**
      * @psalm-pure
     */
    public function deleteDirectory(string $location): void
    {
        // no-op (not supported)
    }

    /**
      * @psalm-pure
     */
    public function createDirectory(string $location, Config $config): void
    {
        // no-op (not supported)
    }

    /**
      * @psalm-pure
     */
    public function setVisibility(string $path, string $visibility): void
    {
        // no-op (not supported)
    }

    /**
      * @psalm-pure
     */
    public function visibility(string $path): FileAttributes
    {
        throw new \RuntimeException('Visibility is not supported by NextphpFilesystemAdapter.');
    }

    /**
      * @psalm-pure
     */
    public function mimeType(string $path): FileAttributes
    {
        throw new \RuntimeException('MIME type is not supported by NextphpFilesystemAdapter.');
    }

    /**
      * @psalm-pure
     */
    public function lastModified(string $path): FileAttributes
    {
        throw new \RuntimeException('Last modified is not supported by NextphpFilesystemAdapter.');
    }

    /**
      * @psalm-pure
     */
    public function fileSize(string $path): FileAttributes
    {
        throw new \RuntimeException('File size is not supported by NextphpFilesystemAdapter.');
    }

    /**
      * @psalm-pure
     */
    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
    }
}

