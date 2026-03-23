<?php

declare(strict_types=1);

namespace Nextphp\Mail;

final class QueuedMailer implements MailerInterface
{
    /** @var Mailable[] */
    private array $queue = [];

    public function __construct(
        private readonly MailerInterface $transport,
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $this->queue[] = $mailable;
    }

    public function flush(): int
    {
        $sent = 0;
        while ($mail = array_shift($this->queue)) {
            $this->transport->send($mail);
            $sent++;
        }

        return $sent;
    }

    public function pendingCount(): int
    {
        return count($this->queue);
    }
}
