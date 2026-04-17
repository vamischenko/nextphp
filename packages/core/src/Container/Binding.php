<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

use Closure;

/**
 * @psalm-immutable
 */
final readonly class Binding
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public BindingType $type,
        public Closure|string|null $concrete,
        public ?object $instance = null,
    ) {
    }
}
