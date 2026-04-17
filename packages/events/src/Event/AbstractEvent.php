<?php

declare(strict_types=1);

namespace Nextphp\Events\Event;

use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    /**
      * @psalm-external-mutation-free
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
