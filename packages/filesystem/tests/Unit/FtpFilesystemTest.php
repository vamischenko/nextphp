<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Tests\Unit;

use Nextphp\Filesystem\Ftp\FtpClientInterface;
use Nextphp\Filesystem\FtpFilesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FtpFilesystem::class)]
final class FtpFilesystemTest extends TestCase
{
    private function makeClient(array $methods = []): FtpClientInterface
    {
        $client = $this->createMock(FtpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('login')->willReturn(true);
        $client->method('pasv')->willReturn(true);

        foreach ($methods as $method => $return) {
            $client->method($method)->willReturn($return);
        }

        return $client;
    }

    #[Test]
    public function putWritesFileViaTempAndCallsFtpPut(): void
    {
        $client = $this->createMock(FtpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('login')->willReturn(true);
        $client->method('pasv')->willReturn(true);
        $client->expects(self::once())->method('put')->willReturn(true);

        $fs = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);
        $fs->put('/remote/file.txt', 'hello');
    }

    #[Test]
    public function getDownloadsAndReturnsContent(): void
    {
        $client = $this->createMock(FtpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('login')->willReturn(true);
        $client->method('pasv')->willReturn(true);
        $client->method('get')->willReturnCallback(
            static function (string $localPath): bool {
                file_put_contents($localPath, 'remote content');
                return true;
            },
        );

        $fs      = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);
        $content = $fs->get('/remote/file.txt');

        self::assertSame('remote content', $content);
    }

    #[Test]
    public function existsReturnsTrueWhenFileInListing(): void
    {
        $client = $this->makeClient(['nlist' => '/remote/file.txt']);

        $fs = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);
        self::assertTrue($fs->exists('/remote/file.txt'));
    }

    #[Test]
    public function existsReturnsFalseWhenNotInListing(): void
    {
        $client = $this->makeClient(['nlist' => '/remote/other.txt']);

        $fs = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);
        self::assertFalse($fs->exists('/remote/file.txt'));
    }

    #[Test]
    public function deleteCallsFtpDelete(): void
    {
        $client = $this->createMock(FtpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('login')->willReturn(true);
        $client->method('pasv')->willReturn(true);
        $client->expects(self::once())->method('delete')->with('/remote/file.txt');

        $fs = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);
        $fs->delete('/remote/file.txt');
    }

    #[Test]
    public function urlReturnsDefaultFtpUrl(): void
    {
        $client = $this->makeClient();
        $fs     = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);

        self::assertSame('ftp://ftp.example.com/remote/file.txt', $fs->url('/remote/file.txt'));
    }

    #[Test]
    public function urlWithBaseUrlOverride(): void
    {
        $client = $this->makeClient();
        $fs     = new FtpFilesystem('ftp.example.com', 'user', 'pass', baseUrl: 'https://cdn.example.com', client: $client);

        self::assertSame('https://cdn.example.com/file.txt', $fs->url('/file.txt'));
    }

    #[Test]
    public function readStreamReturnsResourceWithContent(): void
    {
        $client = $this->createMock(FtpClientInterface::class);
        $client->method('connect')->willReturn(true);
        $client->method('login')->willReturn(true);
        $client->method('pasv')->willReturn(true);
        $client->method('get')->willReturnCallback(static function (string $localPath): bool {
            file_put_contents($localPath, 'stream content');
            return true;
        });

        $fs     = new FtpFilesystem('ftp.example.com', 'user', 'pass', client: $client);
        $stream = $fs->readStream('/file.txt');

        self::assertIsResource($stream);
        self::assertSame('stream content', stream_get_contents($stream));
    }
}
