<?php

declare(strict_types=1);

namespace Nextphp\Events;

interface EventSubscriberInterface
{
    /**
     * @return array<string, string> eventClass => method
     */
    public static function getSubscribedEvents(): array;
}
