<?php

declare(strict_types=1);

namespace Nextphp\Mail\Tests\Unit;

use Nextphp\Mail\AdvancedSmtpMailer;
use Nextphp\Mail\Mailable;
use Nextphp\Mail\Smtp\SmtpTransportInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdvancedSmtpMailer::class)]
final class AdvancedSmtpMailerTest extends TestCase
{
    #[Test]
    public function sendsEhloAuthStartTlsFlowAndRetries(): void
    {
        $transport = new FailingOnceTransport();
        $mailer = new AdvancedSmtpMailer(
            host: 'smtp.local',
            port: 2525,
            from: 'from@test.dev',
            username: 'user',
            password: 'pass',
            startTls: true,
            maxRetries: 2,
            transport: $transport,
        );

        $mailer->send(new AdvancedMail());

        self::assertGreaterThan(1, $transport->connectCalls);
        self::assertContains('STARTTLS', $transport->commands);
        self::assertContains('AUTH LOGIN', $transport->commands);
        self::assertContains('MAIL FROM:<from@test.dev>', $transport->commands);
    }
}

final class FailingOnceTransport implements SmtpTransportInterface
{
    public int $connectCalls = 0;
    /** @var string[] */
    public array $commands = [];

    public function connect(string $host, int $port): void
    {
        $this->connectCalls++;
        if ($this->connectCalls === 1) {
            throw new \RuntimeException('first failure');
        }
    }

    public function read(): string
    {
        return '250 OK';
    }

    public function write(string $command): void
    {
        $this->commands[] = $command;
    }

    public function close(): void
    {
    }
}

final class AdvancedMail extends Mailable
{
    public function subject(): string
    {
        return 'Advanced';
    }

    public function to(): string
    {
        return 'to@test.dev';
    }

    public function html(): string
    {
        return '<p>Advanced</p>';
    }
}
