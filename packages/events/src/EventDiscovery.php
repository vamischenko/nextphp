<?php

declare(strict_types=1);

namespace Nextphp\Events;

use Nextphp\Events\Attribute\ListensTo;

/**
 * Registers listeners that are annotated with #[ListensTo] attributes.
 *
 * Example:
 *   EventDiscovery::register($dispatcher, [
 *       SendWelcomeEmail::class,
 *       NotifyAdmin::class,
 *   ]);
 *
 * Each class may carry multiple #[ListensTo] attributes (one per event).
 * The `method` field defaults to `handle`; override it in the attribute.
 */
final class EventDiscovery
{
    /**
     * @param class-string[] $listenerClasses
     */
    public static function register(EventDispatcher $dispatcher, array $listenerClasses): void
    {
        foreach ($listenerClasses as $class) {
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(ListensTo::class);

            foreach ($attributes as $attribute) {
                /** @var ListensTo $listensTo */
                $listensTo = $attribute->newInstance();
                $method    = $listensTo->method;

                $listener = static function (object $event) use ($class, $method): void {
                    $instance  = new $class();
                    $reflMethod = new \ReflectionMethod($instance, $method);
                    $reflMethod->invoke($instance, $event);
                };

                $dispatcher->addListener($listensTo->eventClass, $listener, $listensTo->priority);
            }
        }
    }
}
