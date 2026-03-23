<?php

declare(strict_types=1);

namespace Nextphp\Mail;

abstract class Mailable
{
    abstract public function subject(): string;

    abstract public function to(): string;

    abstract public function html(): string;

    public function text(): string
    {
        return strip_tags($this->html());
    }
}
