<?php

declare(strict_types=1);

namespace Nextphp\Http\Session;

/**
 * File-backed session.
 *
 * Each session is stored as a serialized PHP file in $storagePath.
 */
final class FileSession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    private string $id;

    private bool $started = false;

    public function __construct(
        private readonly string $storagePath,
        string $id = '',
    ) {
        $this->id = $id !== '' ? $id : $this->newId();
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->started = true;
        $file          = $this->filePath();

        if (is_file($file)) {
            $contents = file_get_contents($file);
            if ($contents !== false) {
                /** @var array<string, mixed>|false $unserialized */
                $unserialized = unserialize($contents);
                if (is_array($unserialized)) {
                    $this->data = $unserialized;
                }
            }
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function regenerate(): void
    {
        $old      = $this->filePath();
        $this->id = $this->newId();

        if (is_file($old)) {
            @unlink($old);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function flush(): void
    {
        $this->data = [];
    }

    public function save(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }

        file_put_contents($this->filePath(), serialize($this->data));
    }

    private function filePath(): string
    {
        return rtrim($this->storagePath, '/') . '/sess_' . $this->id;
    }

    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
