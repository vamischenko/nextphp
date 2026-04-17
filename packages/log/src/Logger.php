<?php

declare(strict_types=1);

namespace Nextphp\Log;

use Psr\Log\AbstractLogger;

/**
 * PSR-3 Logger implementation.
 *
 * Supports multiple handlers (file, stderr, null, etc.) and a minimum log level.
 */
final class Logger extends AbstractLogger
{
    /** @var LogHandlerInterface[] */
    private array $handlers = [];

    private int $minSeverity;

    /**
      * @psalm-mutation-free
     */
    public function __construct(LogLevel $minLevel = LogLevel::Debug)
    {
        $this->minSeverity = $minLevel->severity();
    }

    /**
      * @psalm-external-mutation-free
     */
    public function pushHandler(LogHandlerInterface $handler): static
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @param array<mixed> $context
     */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $lvl = LogLevel::from((string) $level);

        if ($lvl->severity() < $this->minSeverity) {
            return;
        }

        $record = new LogRecord(
            level: $lvl,
            message: $this->interpolate((string) $message, $context),
            context: $context,
            datetime: new \DateTimeImmutable(),
        );

        foreach ($this->handlers as $handler) {
            $handler->handle($record);
        }
    }

    /**
     * @param array<mixed> $context
       * @psalm-pure
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = match (true) {
                $val instanceof \Stringable, is_string($val) => (string) $val,
                is_int($val), is_float($val)                 => (string) $val,
                is_bool($val)                                => $val ? 'true' : 'false',
                $val === null                                => 'null',
                is_array($val)                               => json_encode($val) !== false ? json_encode($val) : '[]',
                is_object($val)                              => $val::class,
                default                                      => '[unknown]',
            };
        }

        return strtr($message, $replace);
    }
}
