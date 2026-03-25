<?php

declare(strict_types=1);

namespace Nextphp\Events\Tests\Unit;

use Nextphp\Events\Attribute\ListensTo;
use Nextphp\Events\EventDiscovery;
use Nextphp\Events\EventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventDiscovery::class)]
#[CoversClass(ListensTo::class)]
final class EventDiscoveryTest extends TestCase
{
    #[Test]
    public function discoversAndRegistersListenerFromAttribute(): void
    {
        $dispatcher = new EventDispatcher();
        EventDiscovery::register($dispatcher, [DiscoveredListener::class]);

        $event = new DiscoveredEvent();
        $dispatcher->dispatch($event);

        self::assertTrue($event->handled);
    }

    #[Test]
    public function listenerIsInstantiatedFreshForEachDispatch(): void
    {
        $dispatcher = new EventDispatcher();
        EventDiscovery::register($dispatcher, [CountingListener::class]);

        $e1 = new CountedEvent();
        $e2 = new CountedEvent();
        $dispatcher->dispatch($e1);
        $dispatcher->dispatch($e2);

        // Each dispatch creates a new listener instance — independent calls
        self::assertSame(1, $e1->count);
        self::assertSame(1, $e2->count);
    }

    #[Test]
    public function multipleListensToAttributesOnSameClass(): void
    {
        $dispatcher = new EventDispatcher();
        EventDiscovery::register($dispatcher, [MultiEventListener::class]);

        $e1 = new MultiEvent1();
        $e2 = new MultiEvent2();
        $dispatcher->dispatch($e1);
        $dispatcher->dispatch($e2);

        self::assertTrue($e1->handled);
        self::assertTrue($e2->handled);
    }

    #[Test]
    public function priorityIsRespected(): void
    {
        $dispatcher = new EventDispatcher();
        EventDiscovery::register($dispatcher, [
            LowPriorityListener::class,
            HighPriorityListener::class,
        ]);

        $event = new PriorityEvent();
        $dispatcher->dispatch($event);

        self::assertSame(['high', 'low'], $event->log);
    }

    #[Test]
    public function customMethodNameIsUsed(): void
    {
        $dispatcher = new EventDispatcher();
        EventDiscovery::register($dispatcher, [CustomMethodListener::class]);

        $event = new CustomMethodEvent();
        $dispatcher->dispatch($event);

        self::assertTrue($event->handled);
    }
}

// ---- Fixtures ----

final class DiscoveredEvent
{
    public bool $handled = false;
}

#[ListensTo(DiscoveredEvent::class)]
final class DiscoveredListener
{
    public function handle(DiscoveredEvent $event): void
    {
        $event->handled = true;
    }
}

final class CountedEvent
{
    public int $count = 0;
}

#[ListensTo(CountedEvent::class)]
final class CountingListener
{
    public function handle(CountedEvent $event): void
    {
        $event->count++;
    }
}

final class MultiEvent1
{
    public bool $handled = false;
}

final class MultiEvent2
{
    public bool $handled = false;
}

#[ListensTo(MultiEvent1::class)]
#[ListensTo(MultiEvent2::class)]
final class MultiEventListener
{
    public function handle(object $event): void
    {
        if ($event instanceof MultiEvent1) {
            $event->handled = true;
        }
        if ($event instanceof MultiEvent2) {
            $event->handled = true;
        }
    }
}

final class PriorityEvent
{
    /** @var string[] */
    public array $log = [];
}

#[ListensTo(PriorityEvent::class, priority: 10)]
final class HighPriorityListener
{
    public function handle(PriorityEvent $event): void
    {
        $event->log[] = 'high';
    }
}

#[ListensTo(PriorityEvent::class, priority: 1)]
final class LowPriorityListener
{
    public function handle(PriorityEvent $event): void
    {
        $event->log[] = 'low';
    }
}

final class CustomMethodEvent
{
    public bool $handled = false;
}

#[ListensTo(CustomMethodEvent::class, method: 'onEvent')]
final class CustomMethodListener
{
    public function onEvent(CustomMethodEvent $event): void
    {
        $event->handled = true;
    }
}
