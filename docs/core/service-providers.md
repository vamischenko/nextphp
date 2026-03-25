# Service Providers

Service Provider — точка входа для регистрации сервисов в контейнере.

## Создание провайдера

```php
use Nextphp\Core\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Connection::class, function () {
            return new Connection(getenv('DB_DSN'));
        });
    }

    public function boot(): void
    {
        // вызывается после всех register() и Compiler Passes
        // здесь можно использовать $this->app->make(...)
    }
}
```

## Регистрация провайдеров

```php
$container = new Container();
$container->registerProvider(new DatabaseServiceProvider($container));
$container->registerProvider(new CacheServiceProvider($container));
$container->boot(); // вызовет register() → passes → boot() у всех провайдеров
```

## Порядок вызовов

```
register() всех провайдеров
    ↓
Compiler Passes (process())
    ↓
boot() всех провайдеров
```

Это гарантирует, что `boot()` видит полностью собранный контейнер.
