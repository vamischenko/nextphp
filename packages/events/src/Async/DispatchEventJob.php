<?php

declare(strict_types=1);

namespace Nextphp\Events\Async;

use Nextphp\Queue\JobInterface;

final class DispatchEventJob implements JobInterface
{
    public function __construct(
        private readonly object $event,
        private readonly mixed $listener,
    ) {
    }

    public function handle(): void
    {
        ($this->listener)($this->event);
    }
}
