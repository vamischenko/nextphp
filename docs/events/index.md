# Events

`nextphp/events` — PSR-14 диспетчер событий с приоритетами и атрибутами.

## Создание события

```php
final class UserRegistered
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}
}
```

## Слушатели

```php
class SendWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        // отправить письмо на $event->email
    }
}
```

## Диспетчер

```php
use Nextphp\Events\EventDispatcher;

$dispatcher = new EventDispatcher();

// Регистрация вручную
$dispatcher->addListener(UserRegistered::class, [new SendWelcomeEmail(), 'handle']);
$dispatcher->addListener(UserRegistered::class, fn($e) => log($e->userId), priority: 10);

// Dispatch
$dispatcher->dispatch(new UserRegistered(userId: 42, email: 'user@example.com'));
```

## Атрибут #[ListensTo]

```php
use Nextphp\Events\Attribute\ListensTo;

#[ListensTo(UserRegistered::class, priority: 5)]
#[ListensTo(UserDeleted::class)]
class AuditListener
{
    public function handle(object $event): void
    {
        // логировать событие
    }
}
```

## EventDiscovery

```php
use Nextphp\Events\EventDiscovery;

EventDiscovery::register($dispatcher, [
    AuditListener::class,
    NotificationListener::class,
]);
```

## Subscribers

```php
use Nextphp\Events\EventSubscriberInterface;

class UserEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => [['onRegistered', 10], ['sendEmail', 0]],
            UserDeleted::class    => 'onDeleted',
        ];
    }

    public function onRegistered(UserRegistered $event): void { /* ... */ }
    public function sendEmail(UserRegistered $event): void { /* ... */ }
    public function onDeleted(UserDeleted $event): void { /* ... */ }
}

$dispatcher->addSubscriber(new UserEventSubscriber());
```

## Stoppable Events

```php
use Psr\EventDispatcher\StoppableEventInterface;

class BeforeCheckout implements StoppableEventInterface
{
    private bool $stopped = false;

    public function stopPropagation(): void { $this->stopped = true; }
    public function isPropagationStopped(): bool { return $this->stopped; }
}
```
