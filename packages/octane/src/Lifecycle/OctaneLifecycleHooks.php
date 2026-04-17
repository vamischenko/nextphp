<?php

declare(strict_types=1);

namespace Nextphp\Octane\Lifecycle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OctaneLifecycleHooks
{
    /** @var list<callable(): void> */
    private array $workerStartHooks = [];

    /** @var list<callable(ServerRequestInterface): void> */
    private array $requestStartHooks = [];

    /** @var list<callable(ServerRequestInterface, ResponseInterface): void> */
    private array $requestEndHooks = [];

    /** @var list<callable(): void> */
    private array $workerStopHooks = [];

    /**
      * @psalm-external-mutation-free
     */
    public function onWorkerStart(callable $hook): void
    {
        $this->workerStartHooks[] = $hook;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function onRequestStart(callable $hook): void
    {
        $this->requestStartHooks[] = $hook;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function onRequestEnd(callable $hook): void
    {
        $this->requestEndHooks[] = $hook;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function onWorkerStop(callable $hook): void
    {
        $this->workerStopHooks[] = $hook;
    }

    public function fireWorkerStart(): void
    {
        foreach ($this->workerStartHooks as $hook) {
            $hook();
        }
    }

    public function fireRequestStart(ServerRequestInterface $request): void
    {
        foreach ($this->requestStartHooks as $hook) {
            $hook($request);
        }
    }

    public function fireRequestEnd(ServerRequestInterface $request, ResponseInterface $response): void
    {
        foreach ($this->requestEndHooks as $hook) {
            $hook($request, $response);
        }
    }

    public function fireWorkerStop(): void
    {
        foreach ($this->workerStopHooks as $hook) {
            $hook();
        }
    }
}
