# IoC-контейнер

`nextphp/core` — DI-контейнер с autowiring, compiler passes и scoped-биндингами.

## Основные методы

```php
use Nextphp\Core\Container\Container;

$container = new Container();

// Привязка интерфейса к реализации
$container->bind(LoggerInterface::class, FileLogger::class);

// Синглтон
$container->singleton(DatabaseConnection::class, function (Container $c) {
    return new DatabaseConnection($c->make(Config::class));
});

// Готовый экземпляр
$container->instance(Config::class, new Config($_ENV));

// Scoped — сбрасывается при вызове $container->resetScoped()
$container->scoped(RequestContext::class, RequestContext::class);

// Resolve
$logger = $container->make(LoggerInterface::class);
```

## Autowiring

Контейнер читает конструктор через `ReflectionClass` и автоматически подставляет зависимости:

```php
class UserService
{
    public function __construct(
        private readonly UserRepository $repo,
        private readonly LoggerInterface $logger,
    ) {}
}

$service = $container->make(UserService::class); // всё разрешается автоматически
```

## Атрибуты

```php
use Nextphp\Core\Attributes\Singleton;
use Nextphp\Core\Attributes\Inject;

#[Singleton]
class CacheService
{
    #[Inject]
    private LoggerInterface $logger;
}
```

## Compiler Passes

Passes запускаются после всех `register()`, но до `boot()`. Используются для оптимизации в production (замена реализаций, удаление лишних биндингов):

```php
use Nextphp\Core\Container\CompilerPassInterface;
use Psr\Container\ContainerInterface;

class CacheWarmupPass implements CompilerPassInterface
{
    public function process(ContainerInterface $container, array &$bindings): void
    {
        // заменить CacheInterface → PreloadedCache в production
        $bindings[CacheInterface::class] = PreloadedCache::class;
    }
}

$container->addCompilerPass(new CacheWarmupPass());
$container->boot(); // pass запустится здесь
```

## Service Providers

```php
use Nextphp\Core\Providers\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Mailer::class, SmtpMailer::class);
    }

    public function boot(): void
    {
        // запускается после всех register() и compiler passes
    }
}
```
