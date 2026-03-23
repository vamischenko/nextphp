<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

interface ServiceProviderInterface
{
    public function register(ContainerInterface $container): void;

    public function boot(ContainerInterface $container): void;
}
