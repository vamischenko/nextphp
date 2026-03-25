# Middleware

PSR-15 middleware pipeline.

## Создание middleware

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');

        if (!$this->isValid($token)) {
            return new Response(401, [], 'Unauthorized');
        }

        return $handler->handle($request);
    }
}
```

## Pipeline

```php
use Nextphp\Http\Middleware\Pipeline;
use Nextphp\Http\Handler\CallableHandler;

$handler = new CallableHandler(function (ServerRequestInterface $req): ResponseInterface {
    return new Response(200, [], 'OK');
});

$pipeline = new Pipeline($handler);
$pipeline = $pipeline->pipe(new AuthMiddleware())
                     ->pipe(new CorsMiddleware())
                     ->pipe(new RateLimitMiddleware(...));

$response = $pipeline->handle($request);
```

## Rate Limiting

```php
use Nextphp\Routing\RateLimit\ArrayRateLimiter;
use Nextphp\Routing\RateLimit\RateLimitMiddleware;

$limiter = new ArrayRateLimiter();

$middleware = new RateLimitMiddleware(
    limiter: $limiter,
    maxAttempts: 60,
    decaySeconds: 60,
    keyResolver: fn($req) => $req->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
);
```

При превышении лимита: `429 Too Many Requests` с заголовками:
- `Retry-After: 42`
- `X-RateLimit-Limit: 60`
- `X-RateLimit-Remaining: 0`

## Встроенные middleware

| Класс | Описание |
|-------|----------|
| `CookieMiddleware` | Читает `Cookie` заголовок, добавляет `Set-Cookie` |
| `SessionMiddleware` | Инициализирует и сохраняет сессию |
| `RateLimitMiddleware` | Ограничение запросов (sliding window) |
| `DebugBarMiddleware` | Вставляет debug-панель в HTML ответы |
