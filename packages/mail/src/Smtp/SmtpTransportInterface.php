<?php

declare(strict_types=1);

namespace Nextphp\Mail\Smtp;

interface SmtpTransportInterface
{
    public function connect(string $host, int $port): void;

    public function read(): string;

    public function write(string $command): void;

    public function close(): void;
}
