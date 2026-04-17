<?php

declare(strict_types=1);

namespace Nextphp\Mail;

final class QueuedMailer implements MailerInterface
{
    /** @var Mailable[] */
    private array $queue = [];

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly MailerInterface $transport,
    ) {
    }

    /**
      * @psalm-external-mutation-free
     */
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

    /**
      * @psalm-mutation-free
     */
    public function pendingCount(): int
    {
        return count($this->queue);
    }
}
