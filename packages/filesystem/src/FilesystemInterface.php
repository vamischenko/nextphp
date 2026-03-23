<?php

declare(strict_types=1);

namespace Nextphp\Filesystem;

interface FilesystemInterface
{
    public function put(string $path, string $contents): void;

    public function get(string $path): string;

    public function exists(string $path): bool;

    public function delete(string $path): void;

    public function url(string $path): string;

    public function signedUrl(string $path, int $expiresInSeconds): string;

    /**
     * @return resource
     */
    public function readStream(string $path);

    /**
     * @param resource $stream
     */
    public function writeStream(string $path, $stream): void;
}
