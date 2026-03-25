# Атрибуты PHP

Nextphp использует нативные PHP 8.2+ атрибуты для декларативной конфигурации.

## #[Singleton]

Помечает класс как синглтон — контейнер создаёт один экземпляр и переиспользует его:

```php
use Nextphp\Core\Attributes\Singleton;

#[Singleton]
class ConfigService
{
    public function __construct(private array $data) {}
}
```

## #[Inject]

Внедрение зависимости в свойство (property injection):

```php
use Nextphp\Core\Attributes\Inject;

class UserController
{
    #[Inject]
    private UserRepository $repo;
}
```

## #[ListensTo] (nextphp/events)

Декларативная подписка на события:

```php
use Nextphp\Events\Attribute\ListensTo;

#[ListensTo(UserRegistered::class, priority: 10)]
#[ListensTo(UserUpdated::class)]
class NotificationListener
{
    public function handle(object $event): void
    {
        // обрабатывает UserRegistered и UserUpdated
    }
}
```

Регистрация через EventDiscovery:

```php
use Nextphp\Events\EventDiscovery;

EventDiscovery::register($dispatcher, [
    NotificationListener::class,
    AuditListener::class,
]);
```
