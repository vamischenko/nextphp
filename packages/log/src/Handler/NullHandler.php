<?php

declare(strict_types=1);

namespace Nextphp\Log\Handler;

use Nextphp\Log\LogHandlerInterface;
use Nextphp\Log\LogRecord;

/** Discards all log records. Useful in tests. */
final class NullHandler implements LogHandlerInterface
{
    /**
      * @psalm-mutation-free
     */
    public function handle(LogRecord $record): void
    {
        // intentionally empty
    }
}
