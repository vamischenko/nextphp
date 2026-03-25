# Logger

`nextphp/log` — PSR-3 логгер с цепочкой обработчиков и интерполяцией контекста.

## Создание

```php
use Nextphp\Log\Logger;
use Nextphp\Log\Handler\StreamHandler;
use Nextphp\Log\LogLevel;

$logger = new Logger();
$logger->pushHandler(new StreamHandler('/var/log/app.log', minLevel: LogLevel::Warning));
$logger->pushHandler(new StreamHandler('php://stderr', minLevel: LogLevel::Debug));
```

## Уровни (RFC 5424)

| Метод | Уровень |
|-------|---------|
| `emergency()` | Система неработоспособна |
| `alert()` | Требует немедленного действия |
| `critical()` | Критическая ошибка |
| `error()` | Ошибка в работе |
| `warning()` | Предупреждение |
| `notice()` | Значимое событие |
| `info()` | Информационное сообщение |
| `debug()` | Отладочная информация |

## Использование

```php
$logger->info('User logged in', ['user_id' => 42]);
$logger->error('Payment failed', ['order_id' => 100, 'reason' => 'timeout']);
$logger->debug('Cache miss for key {key}', ['key' => 'user:42']);
// → "Cache miss for key user:42"
```

Контекст интерполируется: `{key}` заменяется значением `$context['key']`.

## Обработчики

```php
use Nextphp\Log\Handler\ArrayHandler;
use Nextphp\Log\Handler\NullHandler;
use Nextphp\Log\Handler\StreamHandler;

// StreamHandler — запись в файл или ресурс
new StreamHandler('/var/log/app.log', minLevel: LogLevel::Error);
new StreamHandler('php://stdout');

// ArrayHandler — для тестов
$handler = new ArrayHandler();
$logger->pushHandler($handler);
$logger->info('test');
$records = $handler->getRecords(); // LogRecord[]

// NullHandler — /dev/null
$logger->pushHandler(new NullHandler());
```

## Кастомный обработчик

```php
use Nextphp\Log\LogHandlerInterface;
use Nextphp\Log\LogRecord;

class SlackHandler implements LogHandlerInterface
{
    public function handle(LogRecord $record): void
    {
        if ($record->level->severity() <= LogLevel::Error->severity()) {
            sendSlackMessage($record->message);
        }
    }
}
```
