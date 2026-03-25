# Атрибутные маршруты

Маршруты можно определять прямо в контроллере через PHP-атрибуты.

```php
use Nextphp\Routing\Attributes\Route;
use Nextphp\Routing\Attributes\Middleware;

class UserController
{
    #[Route('GET', '/users')]
    public function index(): ResponseInterface { /* ... */ }

    #[Route('GET', '/users/{id}')]
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        // ...
    }

    #[Route('POST', '/users')]
    #[Middleware(AuthMiddleware::class)]
    public function store(ServerRequestInterface $request): ResponseInterface { /* ... */ }

    #[Route('DELETE', '/users/{id}')]
    #[Middleware(AuthMiddleware::class)]
    #[Middleware(AdminMiddleware::class)]
    public function destroy(ServerRequestInterface $request): ResponseInterface { /* ... */ }
}
```

## Регистрация контроллеров

```php
use Nextphp\Routing\AttributeRouteLoader;

$loader = new AttributeRouteLoader($router);
$loader->load([
    UserController::class,
    ArticleController::class,
]);
```
