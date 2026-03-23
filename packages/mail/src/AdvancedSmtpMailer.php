<?php

declare(strict_types=1);

namespace Nextphp\Mail;

use Nextphp\Mail\Smtp\SmtpTransportInterface;
use Nextphp\Mail\Smtp\StreamSmtpTransport;

final class AdvancedSmtpMailer implements MailerInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int $port = 25,
        private readonly string $from = 'noreply@nextphp.dev',
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly bool $startTls = false,
        private readonly int $maxRetries = 2,
        private readonly SmtpTransportInterface $transport = new StreamSmtpTransport(),
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $attempt = 0;
        beginning:
        $attempt++;
        try {
            $this->transport->connect($this->host, $this->port);
            $this->transport->read(); // greeting
            $this->cmd('EHLO localhost');

            if ($this->startTls) {
                $this->cmd('STARTTLS');
                $this->cmd('EHLO localhost');
            }

            if ($this->username !== null && $this->password !== null) {
                $this->cmd('AUTH LOGIN');
                $this->cmd(base64_encode($this->username));
                $this->cmd(base64_encode($this->password));
            }

            $this->cmd(sprintf('MAIL FROM:<%s>', $this->from));
            $this->cmd(sprintf('RCPT TO:<%s>', $mailable->to()));
            $this->cmd('DATA');
            $this->cmd('Subject: ' . $mailable->subject());
            $this->cmd('Content-Type: text/html; charset=UTF-8');
            $this->cmd('');
            $this->cmd($mailable->html());
            $this->cmd('.');
            $this->cmd('QUIT');
            $this->transport->close();
        } catch (\Throwable $e) {
            $this->transport->close();
            if ($attempt <= $this->maxRetries) {
                goto beginning;
            }
            throw $e;
        }
    }

    private function cmd(string $command): void
    {
        $this->transport->write($command);
        $this->transport->read();
    }
}
