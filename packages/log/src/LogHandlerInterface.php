<?php

declare(strict_types=1);

namespace Nextphp\Log;

interface LogHandlerInterface
{
    public function handle(LogRecord $record): void;
}
