# Базовый роутинг

`nextphp/routing` — быстрый роутер на основе Radix Tree.

## Определение маршрутов

```php
use Nextphp\Routing\Router;

$router = new Router();

$router->get('/users',          [UserController::class, 'index']);
$router->post('/users',         [UserController::class, 'store']);
$router->get('/users/{id}',     [UserController::class, 'show']);
$router->put('/users/{id}',     [UserController::class, 'update']);
$router->delete('/users/{id}',  [UserController::class, 'destroy']);

// Callable
$router->get('/ping', fn() => new Response(200, [], 'pong'));
```

## Параметры маршрута

```php
$router->get('/posts/{year}/{slug}', function (ServerRequestInterface $req) {
    $year = $req->getAttribute('year');
    $slug = $req->getAttribute('slug');
    // ...
});
```

## Resource-маршруты

```php
$router->resource('articles', ArticleController::class);
// Создаёт: GET /articles, POST /articles,
//          GET /articles/{id}, PUT /articles/{id}, DELETE /articles/{id}
```

## URL-генератор

```php
$url = $router->url('users.show', ['id' => 42]);
// /users/42
```

## Диспетчеризация

```php
$match = $router->dispatch('GET', '/users/42');
// $match->route   — объект Route
// $match->params  — ['id' => '42']
```

## Обработка 404 / 405

```php
use Nextphp\Http\Exception\NotFoundException;
use Nextphp\Http\Exception\MethodNotAllowedException;

try {
    $match = $router->dispatch($method, $path);
} catch (NotFoundException $e) {
    // 404
} catch (MethodNotAllowedException $e) {
    // 405 — $e->getAllowedMethods()
}
```
