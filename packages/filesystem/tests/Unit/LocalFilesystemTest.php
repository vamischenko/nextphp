<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Tests\Unit;

use Nextphp\Filesystem\LocalFilesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalFilesystem::class)]
final class LocalFilesystemTest extends TestCase
{
    #[Test]
    public function putGetExistsDeleteAndUrl(): void
    {
        $root = sys_get_temp_dir() . '/nextphp_fs_tests';
        @mkdir($root, 0777, true);

        $fs = new LocalFilesystem($root, 'https://cdn.example.com/files');
        $fs->put('avatars/me.txt', 'hello');

        self::assertTrue($fs->exists('avatars/me.txt'));
        self::assertSame('hello', $fs->get('avatars/me.txt'));
        self::assertSame('https://cdn.example.com/files/avatars/me.txt', $fs->url('avatars/me.txt'));
        self::assertStringContainsString('signature=', $fs->signedUrl('avatars/me.txt', 60));

        $fs->delete('avatars/me.txt');
        self::assertFalse($fs->exists('avatars/me.txt'));
    }

    #[Test]
    public function writeAndReadStream(): void
    {
        $root = sys_get_temp_dir() . '/nextphp_fs_tests_stream';
        @mkdir($root, 0777, true);

        $fs = new LocalFilesystem($root);
        $source = fopen('php://temp', 'rb+');
        fwrite($source, 'stream-content');
        rewind($source);

        $fs->writeStream('docs/a.txt', $source);
        fclose($source);

        $read = $fs->readStream('docs/a.txt');
        $content = stream_get_contents($read);
        fclose($read);

        self::assertSame('stream-content', $content);
    }
}
