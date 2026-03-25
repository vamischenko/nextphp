# Debugbar

`nextphp/debugbar` — профайлер-панель, встраиваемая в HTML-ответы через PSR-15 middleware.

## Установка

```php
use Nextphp\Debugbar\DebugBar;
use Nextphp\Debugbar\DebugBarMiddleware;
use Nextphp\Debugbar\Collector\TimelineCollector;
use Nextphp\Debugbar\Collector\MemoryCollector;
use Nextphp\Debugbar\Collector\QueryCollector;

$bar = new DebugBar(enabled: (bool) getenv('APP_DEBUG'));

// Коллекторы
$timeline = new TimelineCollector();
$queries  = new QueryCollector();

$bar->addCollector($timeline);
$bar->addCollector($queries);
$bar->addCollector(new MemoryCollector());

// Middleware
$pipeline->pipe(new DebugBarMiddleware($bar));
```

## Коллекторы

### Timeline

```php
$timeline->start('boot', 'Application Boot');
// ... инициализация провайдеров ...
$timeline->stop('boot');

$timeline->start('db:query', 'DB Query');
$result = $db->query('SELECT * FROM users');
$timeline->stop('db:query');
```

### Query Collector

```php
// Интеграция с ORM:
$queries->addQuery('SELECT * FROM users WHERE id = ?', [42], durationMs: 1.23);
```

### Memory Collector

Собирается автоматически — показывает текущее и пиковое потребление памяти.

### Request Collector

Добавляется автоматически через `DebugBarMiddleware` — показывает метод, URI и заголовки.

## Кастомный коллектор

```php
use Nextphp\Debugbar\Collector\CollectorInterface;

class RedisCollector implements CollectorInterface
{
    private int $commands = 0;

    public function recordCommand(): void { $this->commands++; }

    public function getName(): string { return 'redis'; }

    public function collect(): array
    {
        return ['commands' => $this->commands];
    }
}

$collector = new RedisCollector();
$bar->addCollector($collector);
// ...
$collector->recordCommand();
```

## Внешний вид

Панель фиксируется внизу страницы с тёмной темой:

- **Tabs** с badge-счётчиками (запросы, timeline-события)
- **Timeline** с прогресс-барами относительно общего времени запроса
- **Queries** — список SQL с длительностью каждого
- **Memory** — текущее и пиковое использование
- **Request** — метод, URI, заголовки

Панель НЕ вставляется в:
- JSON-ответы (`Content-Type: application/json`)
- Редиректы (3xx)
- Ответы с явным не-HTML `Content-Type`
