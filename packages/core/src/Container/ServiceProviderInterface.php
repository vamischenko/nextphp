<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

/**
 * @psalm-mutable
 */
interface ServiceProviderInterface
{
    /**
     * @psalm-impure
     */
    public function register(ContainerInterface $container): void;

    /**
     * @psalm-impure
     */
    public function boot(ContainerInterface $container): void;
}
