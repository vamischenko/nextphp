<?php

declare(strict_types=1);

namespace Nextphp\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    private ?StreamInterface $stream;

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly StreamInterface|string $file,
        private readonly ?int $size,
        private readonly int $error,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
    ) {
        if ($this->error === UPLOAD_ERR_OK) {
            if (is_string($file)) {
                $this->stream = null;
            } else {
                $this->stream = $file;
            }
        } else {
            $this->stream = null;
        }
    }

    public function getStream(): StreamInterface
    {
        $this->assertNotMoved();
        $this->assertNoError();

        if ($this->stream !== null) {
            return $this->stream;
        }

        /** @var string $file */
        $file = $this->file;
        $resource = fopen($file, 'r');

        if ($resource === false) {
            throw new RuntimeException(sprintf('Cannot open file: %s', $file));
        }

        return new Stream($resource);
    }

    public function moveTo(string $targetPath): void
    {
        $this->assertNotMoved();
        $this->assertNoError();

        if ($targetPath === '') {
            throw new InvalidArgumentException('Target path cannot be empty.');
        }

        if (is_string($this->file)) {
            $result = PHP_SAPI === 'cli'
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);

            if ($result === false) {
                throw new RuntimeException(sprintf('Cannot move file to: %s', $targetPath));
            }
        } else {
            $destination = fopen($targetPath, 'w');

            if ($destination === false) {
                throw new RuntimeException(sprintf('Cannot open target: %s', $targetPath));
            }

            $this->file->rewind();

            while (!$this->file->eof()) {
                fwrite($destination, $this->file->read(4096));
            }

            fclose($destination);
        }

        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
      * @psalm-mutation-free
     */
    private function assertNotMoved(): void
    {
        if ($this->moved) {
            throw new RuntimeException('File has already been moved.');
        }
    }

    /**
      * @psalm-mutation-free
     */
    private function assertNoError(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf('Uploaded file has error: %d', $this->error));
        }
    }
}
