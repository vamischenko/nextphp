<?php

declare(strict_types=1);

namespace Nextphp\Log;

final readonly class LogRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public LogLevel $level,
        public string $message,
        public array $context,
        public \DateTimeImmutable $datetime,
    ) {
    }
}
