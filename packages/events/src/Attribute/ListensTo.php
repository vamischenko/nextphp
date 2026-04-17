<?php

declare(strict_types=1);

namespace Nextphp\Events\Attribute;

/**
 * Mark a class as a listener for a specific event.
 *
 * Usage:
 *   #[ListensTo(UserRegistered::class)]
 *   final class SendWelcomeEmail
 *   {
 *       public function handle(UserRegistered $event): void { ... }
 *   }
 *
 * Then register via EventDiscovery::register($dispatcher, [SendWelcomeEmail::class]).
 */
/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ListensTo
{
    /**
     * @param class-string $eventClass
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $eventClass,
        public readonly int $priority = 0,
        public readonly string $method = 'handle',
    ) {
    }
}
