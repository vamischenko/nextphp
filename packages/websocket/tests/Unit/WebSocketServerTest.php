<?php

declare(strict_types=1);

namespace Nextphp\WebSocket\Tests\Unit;

use Nextphp\WebSocket\Adapter\RatchetAdapter;
use Nextphp\WebSocket\Adapter\SwooleAdapter;
use Nextphp\WebSocket\ConnectionInterface;
use Nextphp\WebSocket\WebSocketServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebSocketServer::class)]
#[CoversClass(RatchetAdapter::class)]
#[CoversClass(SwooleAdapter::class)]
final class WebSocketServerTest extends TestCase
{
    #[Test]
    public function managesConnectionsAndBroadcasts(): void
    {
        $server = new WebSocketServer();
        $connA = new FakeConnection('a');
        $connB = new FakeConnection('b');

        $server->onOpen($connA);
        $server->onOpen($connB);

        self::assertSame(2, $server->countConnections());

        $server->broadcast('ping');
        self::assertSame(['ping'], $connA->messages);
        self::assertSame(['ping'], $connB->messages);
    }

    #[Test]
    public function adaptersDelegateToServer(): void
    {
        $server = new WebSocketServer();
        $conn = new FakeConnection('1');

        $ratchet = new RatchetAdapter($server);
        $ratchet->handleOpen($conn);
        self::assertSame(1, $server->countConnections());

        $swoole = new SwooleAdapter($server);
        $swoole->handleClose($conn);
        self::assertSame(0, $server->countConnections());
    }
}

final class FakeConnection implements ConnectionInterface
{
    /** @var string[] */
    public array $messages = [];

    public function __construct(private readonly string $id)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function send(string $payload): void
    {
        $this->messages[] = $payload;
    }

    public function close(): void
    {
    }
}
