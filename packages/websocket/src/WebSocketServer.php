<?php

declare(strict_types=1);

namespace Nextphp\WebSocket;

final class WebSocketServer implements MessageHandlerInterface
{
    /** @var array<string, ConnectionInterface> */
    private array $connections = [];

    public function __construct(
        private readonly ?MessageHandlerInterface $handler = null,
    ) {
    }

    public function onOpen(ConnectionInterface $connection): void
    {
        $this->connections[$connection->id()] = $connection;
        $this->handler?->onOpen($connection);
    }

    public function onMessage(ConnectionInterface $connection, string $payload): void
    {
        $this->handler?->onMessage($connection, $payload);
    }

    public function onClose(ConnectionInterface $connection): void
    {
        unset($this->connections[$connection->id()]);
        $this->handler?->onClose($connection);
    }

    public function broadcast(string $payload): void
    {
        foreach ($this->connections as $connection) {
            $connection->send($payload);
        }
    }

    public function countConnections(): int
    {
        return count($this->connections);
    }
}
