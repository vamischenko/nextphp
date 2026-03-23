<?php

declare(strict_types=1);

namespace Nextphp\Mail\Smtp;

interface SmtpClientInterface
{
    public function sendRaw(string $host, int $port, string $payload): void;
}
