<?php

declare(strict_types=1);

namespace Nextphp\WebSocket\Adapter;

use Nextphp\WebSocket\ConnectionInterface;
use Nextphp\WebSocket\WebSocketServer;

/**
 * Minimal bridge-style adapter for Ratchet integration.
 */
final class RatchetAdapter
{
    public function __construct(
        private readonly WebSocketServer $server,
    ) {
    }

    public function handleOpen(ConnectionInterface $connection): void
    {
        $this->server->onOpen($connection);
    }

    public function handleMessage(ConnectionInterface $connection, string $payload): void
    {
        $this->server->onMessage($connection, $payload);
    }

    public function handleClose(ConnectionInterface $connection): void
    {
        $this->server->onClose($connection);
    }
}
