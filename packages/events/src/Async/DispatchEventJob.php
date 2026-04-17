<?php

declare(strict_types=1);

namespace Nextphp\Events\Async;

use Nextphp\Queue\JobInterface;

final class DispatchEventJob implements JobInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly object $event,
        private readonly mixed $listener,
    ) {
    }

    /**
      * @psalm-mutation-free
     */
    public function handle(): void
    {
        ($this->listener)($this->event);
    }
}
