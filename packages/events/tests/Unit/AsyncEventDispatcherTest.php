<?php

declare(strict_types=1);

namespace Nextphp\Events\Tests\Unit;

use Nextphp\Events\Async\AsyncEventDispatcher;
use Nextphp\Events\Async\DispatchEventJob;
use Nextphp\Events\EventDispatcher;
use Nextphp\Queue\InMemoryQueue;
use Nextphp\Queue\Worker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsyncEventDispatcher::class)]
#[CoversClass(DispatchEventJob::class)]
final class AsyncEventDispatcherTest extends TestCase
{
    #[Test]
    public function enqueuesEventListenersIntoQueue(): void
    {
        $base = new EventDispatcher();
        $event = new AsyncUserEvent();
        $base->addListener(AsyncUserEvent::class, function (AsyncUserEvent $event): void {
            $event->handled = true;
        });

        $queue = new InMemoryQueue();
        $dispatcher = new AsyncEventDispatcher($base, $queue);
        $dispatcher->dispatch($event);

        self::assertSame(1, $queue->size());

        $worker = new Worker($queue);
        $worker->runAll();
        self::assertTrue($event->handled);
    }
}

final class AsyncUserEvent
{
    public bool $handled = false;
}
