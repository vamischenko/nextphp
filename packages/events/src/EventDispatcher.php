<?php

declare(strict_types=1);

namespace Nextphp\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

final class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventClass => $method) {
            $this->addListener($eventClass, [$subscriber, $method]);
        }
    }

    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $event;
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;
        $resolved = [];

        foreach ($this->listeners as $class => $listeners) {
            if ($event instanceof $class) {
                foreach ($listeners as $listener) {
                    $resolved[] = $listener;
                }
            }
        }

        return $resolved;
    }
}
