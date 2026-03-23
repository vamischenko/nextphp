# Octane Worker + HttpKernel Bridge

`nextphp/octane` добавляет worker-обвязку для long-running режима.

## Что есть

- `OctaneHttpKernelBridge` — связывает контейнер и `HttpKernel`;
- `OctaneWorker` — обрабатывает запросы и сбрасывает scoped-сервисы между запросами;
- lifecycle hooks:
  - `onWorkerStart`
  - `onRequestStart`
  - `onRequestEnd`
  - `onWorkerStop`

## Пример

```php
use Nextphp\Core\Container\Container;
use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Octane\Worker\OctaneHttpKernelBridge;
use Nextphp\Routing\Router;

$container = new Container();
$router = new Router();
$router->get('/ping', fn () => 'pong');
$kernel = new HttpKernel($router);

$worker = (new OctaneHttpKernelBridge($container))->worker($kernel);
$worker->hooks()->onWorkerStart(fn () => print "worker started\n");
```

Каждый `handle()` вызывает `flushScoped()` в контейнере, чтобы не протекало request-scoped состояние.
