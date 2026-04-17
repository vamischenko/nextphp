<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    /**
      * @psalm-mutation-free
     */
    public function boot(ContainerInterface $container): void
    {
    }
}
