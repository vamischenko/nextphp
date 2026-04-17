<?php

declare(strict_types=1);

namespace Nextphp\Octane\Worker;

use Nextphp\Core\Container\ContainerInterface;
use Nextphp\Http\Kernel\HttpKernel;

/**
 * @psalm-immutable
 */
final class OctaneHttpKernelBridge
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    public function worker(HttpKernel $kernel): OctaneWorker
    {
        return new OctaneWorker($kernel, $this->container);
    }
}
