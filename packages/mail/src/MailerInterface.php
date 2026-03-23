<?php

declare(strict_types=1);

namespace Nextphp\Mail;

interface MailerInterface
{
    public function send(Mailable $mailable): void;
}
