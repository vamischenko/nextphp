<?php

declare(strict_types=1);

namespace Nextphp\Mail\Smtp;

/**
 * @psalm-mutable
 */
interface SmtpClientInterface
{
    /**
     * @psalm-impure
     */
    public function sendRaw(string $host, int $port, string $payload): void;
}
