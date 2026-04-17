<?php

declare(strict_types=1);

namespace Nextphp\Log;

/**
 * @psalm-mutable
 */
interface LogHandlerInterface
{
    /**
     * @psalm-impure
     */
    public function handle(LogRecord $record): void;
}
