<?php

declare(strict_types=1);

namespace Nextphp\Mail;

abstract class Mailable
{
    /**
     * @psalm-impure
     */
    abstract public function subject(): string;

    /**
     * @psalm-impure
     */
    abstract public function to(): string;

    /**
     * @psalm-impure
     */
    abstract public function html(): string;

    public function text(): string
    {
        return strip_tags($this->html());
    }
}
