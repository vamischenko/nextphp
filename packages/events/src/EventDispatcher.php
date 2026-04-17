<?php

declare(strict_types=1);

namespace Nextphp\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

final class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    /** @var array<string, array<int, array{priority: int, listener: callable}>> */
    private array $listeners = [];

    /**
      * @psalm-external-mutation-free
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][] = ['priority' => $priority, 'listener' => $listener];
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventClass => $method) {
            $this->addListener($eventClass, [$subscriber, $method]);
        }
    }

    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }

    /**
      * @psalm-mutation-free
     */
    public function getListenersForEvent(object $event): iterable
    {
        $resolved = [];

        foreach ($this->listeners as $class => $entries) {
            if ($event instanceof $class) {
                foreach ($entries as $entry) {
                    $resolved[] = $entry;
                }
            }
        }

        usort($resolved, static fn (array $a, array $b) => $b['priority'] <=> $a['priority']);

        foreach ($resolved as $entry) {
            yield $entry['listener'];
        }
    }
}
