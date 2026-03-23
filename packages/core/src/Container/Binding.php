<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

use Closure;

final readonly class Binding
{
    public function __construct(
        public BindingType $type,
        public Closure|string|null $concrete,
        public ?object $instance = null,
    ) {
    }
}
