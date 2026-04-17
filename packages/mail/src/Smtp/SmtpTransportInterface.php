<?php

declare(strict_types=1);

namespace Nextphp\Mail\Smtp;

/**
 * @psalm-mutable
 */
interface SmtpTransportInterface
{
    /**
     * @psalm-impure
     */
    public function connect(string $host, int $port): void;

    /**
     * @psalm-impure
     */
    public function read(): string;

    /**
     * @psalm-impure
     */
    public function write(string $command): void;

    /**
     * @psalm-impure
     */
    public function close(): void;
}
