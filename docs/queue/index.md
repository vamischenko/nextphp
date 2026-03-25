# Queue

`nextphp/queue` — система очередей с batching, failed jobs и retry.

## Создание задачи

```php
use Nextphp\Queue\JobInterface;

class SendEmailJob implements JobInterface
{
    public function __construct(
        private readonly string $to,
        private readonly string $subject,
    ) {}

    public function handle(): void
    {
        // отправить письмо
    }
}
```

## Dispatch

```php
use Nextphp\Queue\Queue;

$queue = new Queue(); // in-memory
$queue->push(new SendEmailJob('user@example.com', 'Welcome!'));

// С задержкой
$queue->later(new SendEmailJob(...), delay: 60);
```

## Worker

```php
use Nextphp\Queue\Worker;
use Nextphp\Queue\FailedJobStore;

$failedStore = new FailedJobStore($pdo);
$failedStore->createSchema();

$worker = new Worker($queue, failedJobStore: $failedStore, maxAttempts: 3);
$worker->run(); // блокирующий цикл

// Одна задача
$worker->runOnce();
```

## Job Batching

```php
use Nextphp\Queue\Batch\Batch;

$batch = new Batch();
$batch->add(
    new ProcessImageJob('photo1.jpg'),
    new ProcessImageJob('photo2.jpg'),
    new ProcessImageJob('photo3.jpg'),
);

$batch
    ->then(fn() => Cache::forget('gallery'))  // все успешны
    ->catch(fn() => notify('Batch failed'))   // хотя бы одна ошибка
    ->finally(fn() => log('Batch done'))      // всегда
    ->dispatch();
```

## Failed Jobs

```php
$failedStore = new FailedJobStore($pdo);

// Список провальных задач
$failed = $failedStore->all();
// [['id' => 1, 'job' => '...', 'error' => 'Connection refused', 'failed_at' => ...]]

// Повторная попытка
$failedStore->retry(id: 1, queue: $queue);

// Удалить
$failedStore->delete(1);

// Очистить всё
$failedStore->flush();
```

## Delayed Jobs

```php
$queue->later(new SyncInventoryJob(), delay: 300); // через 5 минут
```
