<?php

declare(strict_types=1);

namespace Nextphp\Routing\Attributes;

use Attribute;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class Route
{
    /**
     * @param string[] $methods
     * @param string[] $middleware
     */
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $path,
        public readonly array $methods = ['GET'],
        public readonly string $name = '',
        public readonly array $middleware = [],
        public readonly array $can = [],
    ) {
    }
}
