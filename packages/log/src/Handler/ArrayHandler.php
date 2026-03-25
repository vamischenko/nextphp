<?php

declare(strict_types=1);

namespace Nextphp\Log\Handler;

use Nextphp\Log\LogHandlerInterface;
use Nextphp\Log\LogRecord;

/** Collects records in memory. Useful for testing. */
final class ArrayHandler implements LogHandlerInterface
{
    /** @var LogRecord[] */
    private array $records = [];

    public function handle(LogRecord $record): void
    {
        $this->records[] = $record;
    }

    /** @return LogRecord[] */
    public function records(): array
    {
        return $this->records;
    }

    public function clear(): void
    {
        $this->records = [];
    }
}
