<?php

declare(strict_types=1);

namespace Nextphp\WebSocket;

interface MessageHandlerInterface
{
    public function onOpen(ConnectionInterface $connection): void;

    public function onMessage(ConnectionInterface $connection, string $payload): void;

    public function onClose(ConnectionInterface $connection): void;
}
