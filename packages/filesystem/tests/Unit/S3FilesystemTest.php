<?php

declare(strict_types=1);

namespace Nextphp\Filesystem\Tests\Unit;

use Nextphp\Filesystem\S3Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(S3Filesystem::class)]
final class S3FilesystemTest extends TestCase
{
    #[Test]
    public function providesObjectStorageAndSignedUrls(): void
    {
        $fs = new S3Filesystem('nextphp-bucket', 'eu-central-1');
        $fs->put('images/a.png', 'png-bytes');

        self::assertTrue($fs->exists('images/a.png'));
        self::assertSame('png-bytes', $fs->get('images/a.png'));
        self::assertStringContainsString('s3.eu-central-1.amazonaws.com', $fs->url('images/a.png'));
        self::assertStringContainsString('X-Signature=', $fs->signedUrl('images/a.png', 30));
    }
}
