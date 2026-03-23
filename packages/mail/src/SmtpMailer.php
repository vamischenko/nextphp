<?php

declare(strict_types=1);

namespace Nextphp\Mail;

use Nextphp\Mail\Smtp\SmtpClientInterface;
use Nextphp\Mail\Smtp\SocketSmtpClient;

final class SmtpMailer implements MailerInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int $port = 25,
        private readonly string $from = 'noreply@nextphp.dev',
        private readonly SmtpClientInterface $client = new SocketSmtpClient(),
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $payload = $this->buildPayload($mailable);
        $this->client->sendRaw($this->host, $this->port, $payload);
    }

    private function buildPayload(Mailable $mailable): string
    {
        return implode("\r\n", [
            sprintf('HELO %s', $this->host),
            sprintf('MAIL FROM:<%s>', $this->from),
            sprintf('RCPT TO:<%s>', $mailable->to()),
            'DATA',
            sprintf('Subject: %s', $mailable->subject()),
            'Content-Type: text/html; charset=UTF-8',
            '',
            $mailable->html(),
            '.',
            'QUIT',
            '',
        ]);
    }
}
