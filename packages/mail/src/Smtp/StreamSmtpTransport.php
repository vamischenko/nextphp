<?php

declare(strict_types=1);

namespace Nextphp\Mail\Smtp;

final class StreamSmtpTransport implements SmtpTransportInterface
{
    /** @var resource|null */
    private $socket = null;

    /**
      * @psalm-external-mutation-free
     */
    public function connect(string $host, int $port): void
    {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 5.0);
        if ($this->socket === false) {
            throw new \RuntimeException(sprintf('SMTP connect failed: %s (%d)', $errstr, $errno));
        }
    }

    public function read(): string
    {
        if (! is_resource($this->socket)) {
            return '';
        }

        return (string) fgets($this->socket);
    }

    public function write(string $command): void
    {
        if (! is_resource($this->socket)) {
            throw new \RuntimeException('SMTP socket not connected.');
        }
        fwrite($this->socket, $command . "\r\n");
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }
}
