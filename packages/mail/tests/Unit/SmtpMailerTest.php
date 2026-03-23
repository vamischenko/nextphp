<?php

declare(strict_types=1);

namespace Nextphp\Mail\Tests\Unit;

use Nextphp\Mail\Mailable;
use Nextphp\Mail\Smtp\SmtpClientInterface;
use Nextphp\Mail\SmtpMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SmtpMailer::class)]
final class SmtpMailerTest extends TestCase
{
    #[Test]
    public function buildsAndSendsPayload(): void
    {
        $client = new RecordingSmtpClient();
        $mailer = new SmtpMailer('smtp.test', 2525, 'from@test.dev', $client);

        $mailer->send(new SmtpDemoMail());

        self::assertSame('smtp.test', $client->host);
        self::assertSame(2525, $client->port);
        self::assertStringContainsString('MAIL FROM:<from@test.dev>', $client->payload);
        self::assertStringContainsString('RCPT TO:<to@test.dev>', $client->payload);
    }
}

final class RecordingSmtpClient implements SmtpClientInterface
{
    public string $host = '';
    public int $port = 0;
    public string $payload = '';

    public function sendRaw(string $host, int $port, string $payload): void
    {
        $this->host = $host;
        $this->port = $port;
        $this->payload = $payload;
    }
}

final class SmtpDemoMail extends Mailable
{
    public function subject(): string
    {
        return 'Smtp';
    }

    public function to(): string
    {
        return 'to@test.dev';
    }

    public function html(): string
    {
        return '<p>SMTP</p>';
    }
}
