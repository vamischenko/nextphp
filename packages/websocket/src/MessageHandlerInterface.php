<?php

declare(strict_types=1);

namespace Nextphp\WebSocket;

/**
 * @psalm-mutable
 */
interface MessageHandlerInterface
{
    /**
     * @psalm-impure
     */
    public function onOpen(ConnectionInterface $connection): void;

    /**
     * @psalm-impure
     */
    public function onMessage(ConnectionInterface $connection, string $payload): void;

    /**
     * @psalm-impure
     */
    public function onClose(ConnectionInterface $connection): void;
}
