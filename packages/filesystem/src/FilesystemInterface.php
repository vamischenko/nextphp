<?php

declare(strict_types=1);

namespace Nextphp\Filesystem;

/**
 * @psalm-mutable
 */
interface FilesystemInterface
{
    /**
     * @psalm-impure
     */
    public function put(string $path, string $contents): void;

    /**
     * @psalm-impure
     */
    public function get(string $path): string;

    /**
     * @psalm-impure
     */
    public function exists(string $path): bool;

    /**
     * @psalm-impure
     */
    public function delete(string $path): void;

    /**
     * @psalm-impure
     */
    public function url(string $path): string;

    /**
     * @psalm-impure
     */
    public function signedUrl(string $path, int $expiresInSeconds): string;

    /**
     * @return resource
     */
    /**
     * @psalm-impure
     */
    public function readStream(string $path);

    /**
     * @param resource $stream
     */
    /**
     * @psalm-impure
     */
    public function writeStream(string $path, $stream): void;
}
