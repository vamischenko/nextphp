<?php

declare(strict_types=1);

namespace Nextphp\Filesystem;

final class S3Filesystem implements FilesystemInterface
{
    /** @var array<string, string> */
    private array $objects = [];

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $bucket,
        private readonly string $region = 'us-east-1',
        private readonly string $secret = 'nextphp-s3-secret',
    ) {
    }

    /**
      * @psalm-external-mutation-free
     */
    public function put(string $path, string $contents): void
    {
        $this->objects[$path] = $contents;
    }

    /**
      * @psalm-mutation-free
     */
    public function get(string $path): string
    {
        if (! isset($this->objects[$path])) {
            throw new \RuntimeException('Object not found: ' . $path);
        }

        return $this->objects[$path];
    }

    /**
      * @psalm-mutation-free
     */
    public function exists(string $path): bool
    {
        return array_key_exists($path, $this->objects);
    }

    /**
      * @psalm-external-mutation-free
     */
    public function delete(string $path): void
    {
        unset($this->objects[$path]);
    }

    /**
      * @psalm-mutation-free
     */
    public function url(string $path): string
    {
        return sprintf('https://%s.s3.%s.amazonaws.com/%s', $this->bucket, $this->region, ltrim($path, '/'));
    }

    public function signedUrl(string $path, int $expiresInSeconds): string
    {
        $expires = time() + max(1, $expiresInSeconds);
        $resource = ltrim($path, '/');
        $sig = hash_hmac('sha256', $resource . '|' . $expires, $this->secret);

        return $this->url($resource) . '?X-Expires=' . $expires . '&X-Signature=' . $sig;
    }

    public function readStream(string $path): mixed
    {
        $stream = fopen('php://temp', 'rb+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create stream');
        }
        fwrite($stream, $this->get($path));
        rewind($stream);

        return $stream;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function writeStream(string $path, mixed $stream): void
    {
        if (! is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }
        $content = stream_get_contents($stream);
        $this->put($path, (string) $content);
    }
}
