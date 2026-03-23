<?php

declare(strict_types=1);

namespace Nextphp\Mail\Tests\Unit;

use Nextphp\Mail\ArrayMailer;
use Nextphp\Mail\Mailable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayMailer::class)]
final class ArrayMailerTest extends TestCase
{
    #[Test]
    public function sendStoresMessageInMemory(): void
    {
        $mailer = new ArrayMailer();
        $mailer->send(new WelcomeMail());

        $sent = $mailer->sent();
        self::assertCount(1, $sent);
        self::assertSame('user@example.com', $sent[0]['to']);
        self::assertSame('Welcome', $sent[0]['subject']);
    }
}

final class WelcomeMail extends Mailable
{
    public function subject(): string
    {
        return 'Welcome';
    }

    public function to(): string
    {
        return 'user@example.com';
    }

    public function html(): string
    {
        return '<h1>Hello</h1>';
    }
}
