<?php

declare(strict_types=1);

namespace Nextphp\WebSocket;

/**
 * @psalm-mutable
 */
interface ConnectionInterface
{
    /**
     * @psalm-impure
     */
    public function id(): string;

    /**
     * @psalm-impure
     */
    public function send(string $payload): void;

    /**
     * @psalm-impure
     */
    public function close(): void;
}
