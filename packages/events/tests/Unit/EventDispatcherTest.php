<?php

declare(strict_types=1);

namespace Nextphp\Events\Tests\Unit;

use Nextphp\Events\EventDispatcher;
use Nextphp\Events\EventSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    #[Test]
    public function dispatchCallsListener(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new UserRegisteredEvent();

        $dispatcher->addListener(UserRegisteredEvent::class, function (UserRegisteredEvent $event): void {
            $event->called = true;
        });

        $dispatcher->dispatch($event);

        self::assertTrue($event->called);
    }

    #[Test]
    public function dispatchCallsSubscriberMethods(): void
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new UserSubscriber();
        $event = new UserRegisteredEvent();

        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event);

        self::assertSame(1, $subscriber->handled);
    }
}

final class UserRegisteredEvent
{
    public bool $called = false;
}

final class UserSubscriber implements EventSubscriberInterface
{
    public int $handled = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::class => 'onUserRegistered',
        ];
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $this->handled++;
    }
}
