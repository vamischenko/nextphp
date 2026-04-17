<?php

declare(strict_types=1);

namespace Nextphp\Log;

use Psr\Log\LogLevel as PsrLevel;

enum LogLevel: string
{
    case Emergency = PsrLevel::EMERGENCY;
    case Alert     = PsrLevel::ALERT;
    case Critical  = PsrLevel::CRITICAL;
    case Error     = PsrLevel::ERROR;
    case Warning   = PsrLevel::WARNING;
    case Notice    = PsrLevel::NOTICE;
    case Info      = PsrLevel::INFO;
    case Debug     = PsrLevel::DEBUG;

    /**
      * @psalm-mutation-free
     */
    public function severity(): int
    {
        return match ($this) {
            self::Emergency => 800,
            self::Alert     => 700,
            self::Critical  => 600,
            self::Error     => 500,
            self::Warning   => 400,
            self::Notice    => 300,
            self::Info      => 200,
            self::Debug     => 100,
        };
    }
}
