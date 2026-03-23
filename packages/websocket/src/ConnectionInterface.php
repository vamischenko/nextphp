<?php

declare(strict_types=1);

namespace Nextphp\WebSocket;

interface ConnectionInterface
{
    public function id(): string;

    public function send(string $payload): void;

    public function close(): void;
}
