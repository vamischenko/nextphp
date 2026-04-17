<?php

declare(strict_types=1);

namespace Nextphp\Mail;

/**
 * @psalm-mutable
 */
interface MailerInterface
{
    /**
     * @psalm-impure
     */
    public function send(Mailable $mailable): void;
}
