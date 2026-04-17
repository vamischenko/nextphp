<?php

declare(strict_types=1);

namespace Nextphp\Events;

/**
 * @psalm-mutable
 */
interface EventSubscriberInterface
{
    /**
     * @return array<string, string> eventClass => method
     */
    public static function getSubscribedEvents(): array;
}
