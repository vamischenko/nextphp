<?php

declare(strict_types=1);

namespace Nextphp\Octane\Worker;

use Nextphp\Core\Container\ContainerInterface;
use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Octane\Lifecycle\OctaneLifecycleHooks;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OctaneWorker
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly HttpKernel $kernel,
        private readonly ContainerInterface $container,
        private readonly OctaneLifecycleHooks $hooks = new OctaneLifecycleHooks(),
    ) {
    }

    public function hooks(): OctaneLifecycleHooks
    {
        return $this->hooks;
    }

    public function boot(): void
    {
        $this->hooks->fireWorkerStart();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->hooks->fireRequestStart($request);
        $response = $this->kernel->handle($request);
        $this->hooks->fireRequestEnd($request, $response);
        $this->container->flushScoped();

        return $response;
    }

    public function stop(): void
    {
        $this->hooks->fireWorkerStop();
    }
}
