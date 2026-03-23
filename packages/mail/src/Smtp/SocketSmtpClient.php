<?php

declare(strict_types=1);

namespace Nextphp\Mail\Smtp;

final class SocketSmtpClient implements SmtpClientInterface
{
    public function sendRaw(string $host, int $port, string $payload): void
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, 3.0);
        if ($socket === false) {
            throw new \RuntimeException(sprintf('SMTP connection failed: %s (%d)', $errstr, $errno));
        }

        fwrite($socket, $payload);
        fclose($socket);
    }
}
