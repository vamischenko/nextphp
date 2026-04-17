<?php

declare(strict_types=1);

namespace Nextphp\Log;

/**
 * @psalm-immutable
 */
final readonly class LogRecord
{
    /**
     * @param array<string, mixed> $context
     * @psalm-mutation-free
     */
    public function __construct(
        public LogLevel $level,
        public string $message,
        public array $context,
        public \DateTimeImmutable $datetime,
    ) {
    }
}
