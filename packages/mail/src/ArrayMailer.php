<?php

declare(strict_types=1);

namespace Nextphp\Mail;

final class ArrayMailer implements MailerInterface
{
    /** @var array<int, array<string, string>> */
    private array $sent = [];

    public function send(Mailable $mailable): void
    {
        $this->sent[] = [
            'to' => $mailable->to(),
            'subject' => $mailable->subject(),
            'html' => $mailable->html(),
            'text' => $mailable->text(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function sent(): array
    {
        return $this->sent;
    }
}
