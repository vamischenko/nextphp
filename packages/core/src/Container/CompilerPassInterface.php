<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

/**
 * A compiler pass inspects and transforms container bindings
 * after all service providers have registered their bindings.
 *
 * Typical use-cases:
 *  - tag-based service collection (e.g. collect all "command" bindings)
 *  - replacing or decorating a binding in production
 *  - validating that required bindings exist before boot
 *
 * Passes run once, in the order they were added, inside Container::boot()
 * after register() but before boot() calls on every provider.
 */
/**
 * @psalm-mutable
 */
interface CompilerPassInterface
{
    /**
     * @param array<string, Binding> $bindings  Reference to the container's binding map.
     *                                           Passes MAY read or mutate it directly.
     */
    /**
     * @psalm-impure
     */
    public function process(ContainerInterface $container, array &$bindings): void;
}
