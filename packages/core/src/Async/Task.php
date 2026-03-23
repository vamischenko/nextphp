<?php

declare(strict_types=1);

namespace Nextphp\Core\Async;

use Fiber;
use RuntimeException;
use Throwable;

final class Task
{
    private mixed $result = null;

    private ?Throwable $error = null;

    private bool $completed = false;

    public function __construct(
        private readonly Fiber $fiber,
    ) {
    }

    public function start(): void
    {
        if ($this->fiber->isStarted()) {
            return;
        }
        $this->runFiber();
    }

    public function resume(mixed $value = null): void
    {
        if (!$this->fiber->isSuspended()) {
            return;
        }

        try {
            $this->fiber->resume($value);
            $this->captureIfTerminated();
        } catch (Throwable $e) {
            $this->error = $e;
            $this->completed = true;
        }
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function await(): mixed
    {
        if (!$this->completed) {
            throw new RuntimeException('Task is not completed yet.');
        }
        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->result;
    }

    private function runFiber(): void
    {
        try {
            $this->fiber->start();
            $this->captureIfTerminated();
        } catch (Throwable $e) {
            $this->error = $e;
            $this->completed = true;
        }
    }

    private function captureIfTerminated(): void
    {
        if ($this->fiber->isTerminated()) {
            $this->result = $this->fiber->getReturn();
            $this->completed = true;
        }
    }
}
