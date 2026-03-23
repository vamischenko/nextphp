<?php

declare(strict_types=1);

namespace Nextphp\Octane\Worker;

use Nextphp\Core\Container\ContainerInterface;
use Nextphp\Http\Kernel\HttpKernel;

final class OctaneHttpKernelBridge
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function worker(HttpKernel $kernel): OctaneWorker
    {
        return new OctaneWorker($kernel, $this->container);
    }
}
