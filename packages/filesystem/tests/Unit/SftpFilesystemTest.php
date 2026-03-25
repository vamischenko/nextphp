<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Tests\Unit;

use Nextphp\Filesystem\Sftp\SftpClientInterface;
use Nextphp\Filesystem\SftpFilesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SftpFilesystem::class)]
final class SftpFilesystemTest extends TestCase
{
    private function makeClient(array $methods = []): SftpClientInterface
    {
        $client = $this->createMock(SftpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('authPassword')->willReturn(true);

        foreach ($methods as $method => $return) {
            $client->method($method)->willReturn($return);
        }

        return $client;
    }

    #[Test]
    public function putWritesViaClient(): void
    {
        $client = $this->createMock(SftpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('authPassword')->willReturn(true);
        $client->expects(self::once())->method('write')->with('/remote/file.txt', 'hello')->willReturn(true);

        $fs = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);
        $fs->put('/remote/file.txt', 'hello');
    }

    #[Test]
    public function getReturnsFileContent(): void
    {
        $client = $this->makeClient(['read' => 'remote content']);

        $fs = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);
        self::assertSame('remote content', $fs->get('/file.txt'));
    }

    #[Test]
    public function getThrowsWhenFileNotFound(): void
    {
        $client = $this->makeClient(['read' => false]);

        $fs = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);
        $this->expectException(\RuntimeException::class);
        $fs->get('/missing.txt');
    }

    #[Test]
    public function existsReturnsTrueOnHit(): void
    {
        $client = $this->makeClient(['exists' => true]);
        $fs     = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);

        self::assertTrue($fs->exists('/file.txt'));
    }

    #[Test]
    public function existsReturnsFalseOnMiss(): void
    {
        $client = $this->makeClient(['exists' => false]);
        $fs     = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);

        self::assertFalse($fs->exists('/missing.txt'));
    }

    #[Test]
    public function deleteCallsClient(): void
    {
        $client = $this->createMock(SftpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('authPassword')->willReturn(true);
        $client->expects(self::once())->method('delete')->with('/file.txt');

        $fs = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);
        $fs->delete('/file.txt');
    }

    #[Test]
    public function urlReturnsDefaultSftpUrl(): void
    {
        $client = $this->makeClient();
        $fs     = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);

        self::assertSame('sftp://sftp.example.com/file.txt', $fs->url('/file.txt'));
    }

    #[Test]
    public function urlWithBaseUrl(): void
    {
        $client = $this->makeClient();
        $fs     = new SftpFilesystem('sftp.example.com', 'user', 'pass', baseUrl: 'https://cdn.example.com', client: $client);

        self::assertSame('https://cdn.example.com/file.txt', $fs->url('/file.txt'));
    }

    #[Test]
    public function readStreamReturnsResource(): void
    {
        $client = $this->makeClient(['read' => 'stream data']);
        $fs     = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);

        $stream = $fs->readStream('/file.txt');
        self::assertIsResource($stream);
        self::assertSame('stream data', stream_get_contents($stream));
    }

    #[Test]
    public function connectIsCalledOnlyOnce(): void
    {
        $client = $this->createMock(SftpClientInterface::class);
        $client->expects(self::once())->method('connect')->willReturn(true);
        $client->expects(self::once())->method('authPassword')->willReturn(true);
        $client->method('exists')->willReturn(true);

        $fs = new SftpFilesystem('sftp.example.com', 'user', 'pass', client: $client);
        $fs->exists('/a');
        $fs->exists('/b');
    }
}
