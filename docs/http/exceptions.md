# Обработка исключений

## ExceptionHandler

```php
use Nextphp\Http\Exception\ExceptionHandler;

$handler = new ExceptionHandler(debug: true);

// Возвращает ResponseInterface
$response = $handler->handle($exception, $request);
```

## JSON vs HTML

Формат ответа определяется по заголовку `Accept`:

| Accept | Формат |
|--------|--------|
| `application/json` | JSON |
| `text/html` (или отсутствует) | HTML |

**Production HTML** — минимальная страница с кодом и описанием ошибки.

**Debug HTML** — тёмная страница с:
- классом исключения
- файлом и строкой
- полным цветным стек-трейсом
- цепочкой `$previous`

**Debug JSON:**
```json
{
  "error": "Division by zero",
  "class": "DivisionByZeroError",
  "trace": ["#0 src/Math.php(12): ...]
}
```

## HTTP-исключения

```php
use Nextphp\Http\Exception\HttpException;
use Nextphp\Http\Exception\NotFoundException;
use Nextphp\Http\Exception\MethodNotAllowedException;

throw new NotFoundException('User not found');          // 404
throw new HttpException(403, 'Forbidden');              // 403
throw new MethodNotAllowedException(['GET', 'POST']);   // 405
```

## Интеграция с HttpKernel

```php
use Nextphp\Http\Kernel\HttpKernel;

$kernel = new HttpKernel($router, debug: (bool) getenv('APP_DEBUG'));
$response = $kernel->handle($request);
// исключения перехватываются и превращаются в Response автоматически
```
