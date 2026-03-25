<?php

declare(strict_types=1);

namespace Nextphp\Log\Handler;

use Nextphp\Log\LogHandlerInterface;
use Nextphp\Log\LogLevel;
use Nextphp\Log\LogRecord;

/**
 * Writes log records to a stream (file path or php://stderr, php://stdout).
 */
final class StreamHandler implements LogHandlerInterface
{
    /** @var resource */
    private $stream;

    private int $minSeverity;

    /**
     * @param resource|string $stream  File path or open resource
     */
    public function __construct(
        mixed $stream,
        LogLevel $minLevel = LogLevel::Debug,
        private readonly string $dateFormat = 'Y-m-d H:i:s',
    ) {
        if (is_string($stream)) {
            $resource = fopen($stream, 'ab');
            if ($resource === false) {
                throw new \RuntimeException("Cannot open log stream: {$stream}");
            }
            $this->stream = $resource;
        } else {
            /** @var resource $stream */
            $this->stream = $stream;
        }

        $this->minSeverity = $minLevel->severity();
    }

    public function handle(LogRecord $record): void
    {
        if ($record->level->severity() < $this->minSeverity) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s\n",
            $record->datetime->format($this->dateFormat),
            strtoupper($record->level->value),
            $record->message,
        );

        fwrite($this->stream, $line);
    }
}
