<?php

declare(strict_types=1);

namespace Nextphp\Events\Async;

use Nextphp\Events\EventDispatcher;
use Nextphp\Queue\QueueInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AsyncEventDispatcher implements EventDispatcherInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly EventDispatcher $dispatcher,
        private readonly QueueInterface $queue,
    ) {
    }

    public function dispatch(object $event): object
    {
        foreach ($this->dispatcher->getListenersForEvent($event) as $listener) {
            $this->queue->push(new DispatchEventJob($event, $listener));
        }

        return $event;
    }
}
