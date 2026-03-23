<?php

declare(strict_types=1);

namespace Nextphp\Mail\Tests\Unit;

use Nextphp\Mail\ArrayMailer;
use Nextphp\Mail\Mailable;
use Nextphp\Mail\QueuedMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueuedMailer::class)]
final class QueuedMailerTest extends TestCase
{
    #[Test]
    public function queuesAndFlushesMessages(): void
    {
        $transport = new ArrayMailer();
        $mailer = new QueuedMailer($transport);

        $mailer->send(new DemoMail());
        $mailer->send(new DemoMail());

        self::assertSame(2, $mailer->pendingCount());
        self::assertSame(2, $mailer->flush());
        self::assertCount(2, $transport->sent());
        self::assertSame(0, $mailer->pendingCount());
    }
}

final class DemoMail extends Mailable
{
    public function subject(): string
    {
        return 'Demo';
    }

    public function to(): string
    {
        return 'demo@example.com';
    }

    public function html(): string
    {
        return '<p>Demo</p>';
    }
}
