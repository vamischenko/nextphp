# Группы маршрутов

## Префиксы и middleware

```php
$router->group('/api/v1', function (Router $r) {
    $r->get('/users',       [UserController::class, 'index']);
    $r->get('/users/{id}',  [UserController::class, 'show']);
    $r->post('/users',      [UserController::class, 'store']);
}, middleware: [AuthMiddleware::class, ThrottleMiddleware::class]);
```

## Вложенные группы

```php
$router->group('/admin', function (Router $r) {
    $r->group('/users', function (Router $r) {
        $r->get('/',        [AdminUserController::class, 'index']);
        $r->delete('/{id}', [AdminUserController::class, 'destroy']);
    });

    $r->resource('articles', AdminArticleController::class);
}, middleware: [AdminMiddleware::class]);
```

## API-группы с версионированием

```php
foreach (['v1', 'v2'] as $version) {
    $router->group("/api/{$version}", function (Router $r) use ($version) {
        $controller = "App\\Http\\Controllers\\{$version}\\UserController";
        $r->resource('users', $controller);
    });
}
```
