# Nextphp — Roadmap улучшений

> Объединённый документ на основе анализа 12 PHP-фреймворков:
> Laravel, Symfony, Yii2, CakePHP, Zend/Laminas, CodeIgniter, Slim, Phalcon, FuelPHP, Lumen, Spiral, Zend.
>
> **Легенда статусов:**
> ✅ уже реализовано · 🔴 P0 — критично · 🟠 P1 — высокий · 🟡 P2 — средний · ⚪ P3 — низкий

---

## Содержание

1. [DI-контейнер и ядро](#1-di-контейнер-и-ядро)
2. [HTTP и Routing](#2-http-и-routing)
3. [ORM и база данных](#3-orm-и-база-данных)
4. [Безопасность и Auth](#4-безопасность-и-auth)
5. [Уведомления](#5-уведомления)
6. [Очереди и планировщик](#6-очереди-и-планировщик)
7. [Кэш](#7-кэш)
8. [Валидация и формы](#8-валидация-и-формы)
9. [i18n / Локализация](#9-i18n--локализация)
10. [Console / CLI / DX](#10-console--cli--dx)
11. [Отладка и наблюдаемость](#11-отладка-и-наблюдаемость)
12. [Производительность](#12-производительность)
13. [Тестирование](#13-тестирование)
14. [Модульная архитектура](#14-модульная-архитектура)
15. [API-инструменты](#15-api-инструменты)
16. [Новые пакеты](#16-новые-пакеты)
17. [Итоговый приоритетный план](#17-итоговый-приоритетный-план)

---

## 1. DI-контейнер и ядро

**Источник: Symfony, Phalcon, Spiral, Lumen**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| Autowiring + Compiler Passes | ✅ | — | Реализовано |
| Scoped services (request / singleton / transient) | ✅ | — | Реализовано |
| Атрибуты `#[Singleton]`, `#[Inject]` | ✅ | — | Реализовано |
| **Compiled Container** | ❌ | 🟠 P1 | Генерация PHP-кэша контейнера в production — минимум Reflection при boot. Как Symfony `bin/console cache:warmup` |
| **Deferrable Providers** | ❌ | 🟠 P1 | Провайдер грузится только при первом `make()` его сервиса. Ускоряет boot на 30-50% в больших приложениях |
| **Typed Config DTO** | ❌ | 🟠 P1 | Типизированный конфиг через readonly-классы: `$config->db->host` вместо `config('db.host')`. Валидация при boot |
| **Static Class Scanner** (Tokenizer) | ❌ | 🟠 P1 | Сканирование классов по атрибутам/интерфейсам без загрузки через Reflection — как в Spiral. Нужен для autodiscovery Routes, Listeners |
| Compiled Routes Cache | ❌ | 🟡 P2 | Сериализация дерева роутов в PHP-файл после первого прогона |
| Request-scoped services reset | ❌ | 🟡 P2 | Автоматический сброс scoped-сервисов между запросами в Octane/RoadRunner |

### Реализация Compiled Container

```php
// Генерация при деплое
php nextphp container:compile --output=bootstrap/cache/container.php

// В production bootstrap
if (file_exists('bootstrap/cache/container.php')) {
    $container = require 'bootstrap/cache/container.php'; // без Reflection
} else {
    $container = new Container();
    $container->boot();
}
```

---

## 2. HTTP и Routing

**Источник: Laravel, Symfony, Slim, CakePHP**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| PSR-7/15 полностью | ✅ | — | |
| Rate Limiting (Sliding Window) | ✅ | — | |
| Attribute Routes | ✅ | — | |
| Resource Routes | ✅ | — | |
| **Route Model Binding** | ❌ | 🔴 P0 | `{user}` → автоматический `User::findOrFail($id)`. Можно кастомизировать resolver. Убирает boilerplate из контроллеров |
| **Regex Route Constraints** | ❌ | 🟠 P1 | `{id<\d+>}`, `{slug<[a-z-]+>}` — валидация параметров на уровне роутера |
| **Signed / Temporary URLs** | ❌ | 🟠 P1 | `URL::signedRoute('verify', ['id' => 1], expires: 3600)` — защищённые ссылки для подтверждения email, скачивания файлов |
| **Flash Messages** | ❌ | 🟠 P1 | Типизированные flash: `flash()->success('Saved!')`, `flash()->error('Failed')`. Persist через сессию между редиректами |
| **Middleware Groups** (global/web/api) | ❌ | 🟠 P1 | Декларативные группы с приоритетами: `global → group → route`, aliases |
| OpenAPI генерация из атрибутов | ❌ | 🟡 P2 | `#[ApiDoc(summary: '...', response: UserResource::class)]` → автоматический swagger.json |
| Host-based routing | ❌ | 🟡 P2 | `{subdomain}.example.com → TenantMiddleware` |
| Micro Application mode | ❌ | ⚪ P3 | Минимальный bootstrap без всех провайдеров для serverless/edge |

### Route Model Binding

```php
// Автоматически: {user} → User::find($id) или 404
$router->get('/users/{user}', [UserController::class, 'show']);

// Кастомный resolver
$router->bind('user', fn($value) => User::where('slug', $value)->firstOrFail());

// В контроллере
public function show(User $user): ResponseInterface  // $user уже загружен
```

---

## 3. ORM и база данных

**Источник: Laravel Eloquent, Yii2, Doctrine/Symfony, CakePHP, Phalcon**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| QueryBuilder, Relations, Scopes | ✅ | — | |
| Soft Delete | ✅ | — | |
| Observers | ✅ | — | |
| N+1 warning | ✅ | — | |
| Migrations + Schema Builder | ✅ | — | |
| Model Factories + Faker | ✅ | — | |
| **Model Casting** | ❌ | 🔴 P0 | `protected array $casts = ['meta' => 'array', 'status' => Status::class, 'flags' => AsCollection::class]`. Автоматическое преобразование при get/set |
| **Global Scopes** | ❌ | 🔴 P0 | `protected static function booted(): void { static::addGlobalScope(new ActiveScope()); }` — автоматическая фильтрация всех запросов |
| **Accessors / Mutators** | ❌ | 🔴 P0 | `protected function fullName(): Attribute { return Attribute::make(get: fn() => "$this->first $this->last"); }` |
| **migrate:fresh / migrate:status** | ❌ | 🟠 P1 | CLI-команды для стандартного dev-флоу |
| **Fixture Factory** | ❌ | 🟠 P1 | `UserFactory::new()->admin()->count(5)->create()` — декларативные тестовые данные |
| **Data Mapper (Repository)** | ❌ | 🟡 P2 | Альтернативный слой Entity + Repository без Active Record — для сложных доменных моделей |
| **Unit of Work** | ❌ | 🟡 P2 | Отложенный flush всех изменений одной транзакцией |
| Compiled ORM metadata cache | ❌ | 🟡 P2 | Кэш column-map и relation-map без Reflection в production |
| **Schema Reverse (make:model из БД)** | ❌ | 🟡 P2 | `php nextphp make:model User --from-table=users` — auto-fill свойств из структуры таблицы |

### Model Casting

```php
class User extends Model
{
    protected array $casts = [
        'settings'   => 'array',           // JSON ↔ array
        'status'     => UserStatus::class, // string ↔ Enum
        'created_at' => 'datetime',        // string ↔ DateTimeImmutable
        'score'      => 'float',
        'is_active'  => 'boolean',
    ];
}

$user->settings['theme']; // автоматически декодируется из JSON
$user->status === UserStatus::Active; // автоматически в Enum
```

---

## 4. Безопасность и Auth

**Источник: Symfony Security, Laravel, Yii2, Phalcon**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| Session / Token / JWT Guards | ✅ | — | |
| Gates & Policies | ✅ | — | |
| Password Reset | ✅ | — | |
| Email Verification | ✅ | — | |
| TOTP 2FA | ✅ | — | |
| **RBAC** (роли + разрешения + иерархия) | ❌ | 🔴 P0 | `$rbac->createRole('editor', inherits: ['viewer'])`. Хранение ролей/разрешений в БД. `User::can('post.delete')` |
| **CSRF Middleware** | ❌ | 🟠 P1 | Защита web-форм. Token в сессии, проверка в POST/PUT/PATCH |
| **Security Voters** | ❌ | 🟠 P1 | Symfony-подход: `VoterInterface::vote()` — гибче чем Gate для сложных ACL с несколькими атрибутами |
| **Auth Scaffolding** | ❌ | 🟠 P1 | Готовые контроллеры: Login, Register, ForgotPassword, VerifyEmail — `make:auth` |
| Encrypted sessions | ❌ | 🟡 P2 | Опциональное шифрование данных сессии |

### RBAC

```php
// Настройка
$rbac = new RbacManager($pdo);
$rbac->createPermission('post.create');
$rbac->createPermission('post.delete');
$rbac->createRole('editor',  permissions: ['post.create']);
$rbac->createRole('admin',   inherits: ['editor'], permissions: ['post.delete']);

// Назначение
$rbac->assign('admin', userId: $user->id);

// Проверка
$user->can('post.delete');   // true/false
$gate->authorize('post.delete', [$post]); // Exception если нет доступа
```

---

## 5. Уведомления

**Источник: Laravel Notifications**

> Новый пакет `nextphp/notifications` — единый API для уведомлений через разные каналы.

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| **Notification класс + via()** | ❌ | 🔴 P0 | `public function via($notifiable): array { return ['mail', 'database']; }` |
| **Mail канал** | ❌ | 🔴 P0 | Генерирует `Mailable` из `toMail()` |
| **Database канал** | ❌ | 🔴 P0 | Хранит уведомления в таблице `notifications` |
| **Slack канал** | ❌ | 🟠 P1 | Webhook-based отправка в Slack |
| **SMS канал** (Twilio/Vonage) | ❌ | 🟡 P2 | `toSms()` → интеграция с SMS-провайдерами |
| **Broadcast канал** | ❌ | 🟡 P2 | Real-time через WebSocket |
| **Notifiable trait** | ❌ | 🔴 P0 | Trait для User-модели: `$user->notify(new InvoicePaid($invoice))` |

```php
class InvoicePaid extends Notification
{
    public function __construct(private Invoice $invoice) {}

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Invoice #{$this->invoice->id} paid")
            ->line("Amount: \${$this->invoice->total}")
            ->action('View Invoice', url("/invoices/{$this->invoice->id}"));
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount'     => $this->invoice->total,
        ];
    }
}

// Отправка
$user->notify(new InvoicePaid($invoice));

// Массовая
Notification::send(User::all(), new SystemAlert('Maintenance at 3am'));

// Непрочитанные
$user->unreadNotifications();
$user->markAllNotificationsRead();
```

---

## 6. Очереди и планировщик

**Источник: Laravel, Symfony Messenger, Spiral, CakePHP**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| Worker, Batch, Failed Jobs | ✅ | — | |
| InMemory / Database / Redis Queue | ✅ | — | |
| **Task Scheduler** | ❌ | 🔴 P0 | Cron-like планировщик в коде: `$schedule->call(fn() => ...)->daily()`. Заменяет разрозненные cron-строки |
| **Queue Interceptors / Middleware** | ❌ | 🟠 P1 | Pipeline для jobs: трассировка, retry с backoff, dead-letter routing, rate-limit per job type |
| **Unique Jobs** | ❌ | 🟠 P1 | `implements UniqueJobInterface` — не добавлять задачу в очередь если такая уже есть |
| **AMQP / RabbitMQ Transport** | ❌ | 🟠 P1 | Адаптер для RabbitMQ через AMQP |
| **Queue Dashboard** (Horizon-lite) | ❌ | 🟡 P2 | Простая веб-панель: pending/processing/failed jobs, retry кнопка |

### Task Scheduler

```php
// В AppServiceProvider::boot() или dedicated ScheduleProvider
$schedule->command('reports:generate')->dailyAt('02:00');
$schedule->command('cache:prune')->hourly();
$schedule->call(fn() => DB::table('sessions')->whereExpired()->delete())->everyFifteenMinutes();
$schedule->job(new SyncInventoryJob())->weekdays()->at('09:00');

// Запуск через единственный cron
// * * * * * php /app/nextphp schedule:run >> /dev/null 2>&1
```

---

## 7. Кэш

**Источник: Laravel, Symfony**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| PSR-16, Tags, Remember | ✅ | — | |
| File / Redis / Memcached / Database / Array | ✅ | — | |
| **Cache Locking** | ❌ | 🟠 P1 | `$cache->lock('key', 10)->get(fn() => expensiveCompute())` — предотвращает cache stampede |
| **Distributed Lock** (`nextphp/lock`) | ❌ | 🟠 P1 | Отдельный пакет: `$lock = $factory->create('invoice:42'); $lock->acquire()` — для очередей, финансовых операций |
| **Flexible Cache** | ❌ | 🟡 P2 | Stale-while-revalidate: отдаёт устаревшие данные пока обновляет в фоне |

```php
// Cache Locking (anti-stampede)
$value = $cache->lock('expensive-report', ttl: 30)->get(function () {
    return generateReport(); // выполняется только одним процессом
});
```

---

## 8. Валидация и формы

**Источник: Laravel, Symfony Validator, Yii2**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| Rule-based Validator | ✅ | — | |
| FormRequest | ✅ | — | |
| **Constraint-based валидация** | ❌ | 🟠 P1 | `#[NotBlank]`, `#[Length(min: 3, max: 100)]`, `#[Email]` на свойствах DTO — валидация графа объектов |
| **Валидация вложенных объектов** | ❌ | 🟠 P1 | `#[Valid]` для рекурсивной валидации вложенных DTO |
| **Локализация сообщений** | ❌ | 🟠 P1 | Pluralization, user-friendly field names, контекстные варианты |
| **Precognition** (live-валидация) | ❌ | ⚪ P3 | Валидация без полной отправки формы — для frontend live-feedback |

```php
class RegisterRequest
{
    #[NotBlank]
    #[Length(min: 2, max: 50)]
    public string $name;

    #[NotBlank]
    #[Email]
    #[UniqueInDatabase(table: 'users', column: 'email')]
    public string $email;

    #[NotBlank]
    #[Length(min: 8)]
    public string $password;

    #[Valid]  // рекурсивно валидирует вложенный объект
    public AddressRequest $address;
}
```

---

## 9. i18n / Локализация

**Источник: Symfony Translation, Laravel, Yii2**

> Новый пакет `nextphp/translation`

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| **Базовый перевод** | ❌ | 🔴 P0 | `trans('auth.login')`, файлы `lang/ru.php` / `lang/en.php` |
| **Интерполяция параметров** | ❌ | 🔴 P0 | `trans('welcome', ['name' => 'Alice'])` → "Добро пожаловать, Alice!" |
| **Pluralization** | ❌ | 🔴 P0 | `trans_choice('items', 5)` → "5 предметов" / "5 items" |
| **Смена локали** | ❌ | 🟠 P1 | `$translator->setLocale('ru')`, middleware для определения по Accept-Language |
| **Lazy-loading переводов** | ❌ | 🟡 P2 | Загружать только нужные namespace'ы, не все файлы сразу |
| **ICU формат** | ❌ | 🟡 P2 | Поддержка ICU MessageFormat для сложных pluralization/гендера |

```php
// lang/ru.php
return [
    'welcome'         => 'Добро пожаловать, :name!',
    'items'           => '{0} нет предметов|{1} предмет|[2,*] :count предмета',
    'auth.login'      => 'Войти',
    'auth.logout'     => 'Выйти',
];

// Использование
echo trans('welcome', ['name' => 'Алиса']);   // Добро пожаловать, Алиса!
echo trans_choice('items', 3);                // 3 предмета
```

---

## 10. Console / CLI / DX

**Источник: Laravel Artisan, Symfony Console, Spiral**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| make:model / make:controller / make:migration | ✅ | — | |
| Interactive CLI | ✅ | — | |
| **make:model --from-table** | ❌ | 🟠 P1 | Генерация модели из существующей таблицы БД (`DESCRIBE users`) |
| **make:notification** | ❌ | 🟠 P1 | Генератор класса уведомления |
| **make:feature** | ❌ | 🟡 P2 | Генерация Feature-флага |
| **make:resource** | ❌ | 🟡 P2 | Генерация API Resource |
| **CLI Prompts** (интерактивные) | ❌ | 🟡 P2 | Мульти-селект, автодополнение, спиннеры — как Laravel Prompts |
| **container:compile** | ❌ | 🟡 P2 | Компиляция контейнера в PHP-кэш |
| **route:cache / route:list** | ❌ | 🟡 P2 | Кэш роутов + вывод таблицы всех маршрутов |
| **schedule:run / schedule:list** | ❌ | 🔴 P0 | Запуск планировщика (нужен для Task Scheduler) |

---

## 11. Отладка и наблюдаемость

**Источник: Symfony Profiler, Laravel Telescope/Debugbar, Yii2 Debug**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| DebugBarMiddleware + HTML панель | ✅ | — | Timeline, Memory, Queries, Request |
| **Расширенный Debugbar** | ❌ | 🔴 P0 | Добавить в панель: Cache hits/misses, Events fired, Queue jobs dispatched, Log messages, Exception context |
| **Persistence профиля** | ❌ | 🟠 P1 | Сохранение данных профилирования в SQLite — можно просматривать после запроса через `/debugbar/{id}` |
| **OpenTelemetry Hooks** | ❌ | 🟠 P1 | Spans для HTTP, DB, Cache, Queue — стандарт для APM инструментов (Jaeger, Zipkin, Datadog) |
| **Monolog адаптер** | ❌ | 🟡 P2 | `Nextphp\Log\Logger` может делегировать в Monolog — доступ к его обширной экосистеме handlers |
| **Exception context** | ❌ | 🟡 P2 | В debug-странице показывать: текущий Request dump, сессию, аутентифицированного пользователя |

### Расширенный DebugBar

Панели которых сейчас не хватает:
- **Cache** — `hit` / `miss` / `write` с ключами и TTL
- **Events** — список всех dispatched событий и их listeners
- **Queue** — задачи отправленные в очередь в этом запросе
- **Logs** — записи из Logger за текущий запрос
- **Auth** — текущий user / guard / permissions

---

## 12. Производительность

**Источник: Phalcon, Spiral, Laravel Octane**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| Swoole / RoadRunner (Octane) | ✅ | — | |
| FrankenPHP поддержка | ✅ | — | |
| ReflectionClass кэш | ✅ | — | |
| **Compiled Container** | ❌ | 🟠 P1 | Дамп контейнера в PHP-файл — см. раздел DI |
| **Attribute Cache** | ❌ | 🟠 P1 | Кэш результатов `ReflectionClass::getAttributes()` в APC/файл — не парсить при каждом запросе |
| **OPcache Preloading список** | ❌ | 🟡 P2 | Генерация `preload.php` со всеми классами фреймворка для `opcache.preload` |
| **Serverless / Bref** | ❌ | 🟡 P2 | Готовая конфигурация для деплоя на AWS Lambda через Bref |
| Memory leak guards (Octane) | ❌ | 🟡 P2 | Автоматический сброс статических свойств между запросами в long-running режиме |

---

## 13. Тестирование

**Источник: Laravel, Symfony, CakePHP**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| HTTP TestClient + TestResponse | ✅ | — | |
| MockBuilder + MockeryTrait | ✅ | — | |
| BrowserTestCase (Panther) | ✅ | — | |
| Snapshot Testing | ✅ | — | |
| RefreshDatabase / DatabaseTransactions | ✅ | — | |
| **Notification Testing** | ❌ | 🟠 P1 | `Notification::fake()`, `assertSentTo($user, InvoicePaid::class)` |
| **Mail Testing** | ❌ | 🟠 P1 | `Mail::fake()`, `assertSent(WelcomeMail::class, fn($m) => $m->hasTo('alice@..'))` |
| **Queue Testing** | ❌ | 🟠 P1 | `Queue::fake()`, `assertPushed(SendEmailJob::class)` |
| **Event Testing** | ❌ | 🟠 P1 | `Event::fake()`, `assertDispatched(UserRegistered::class)` |
| **Feature Flag Testing** | ❌ | 🟡 P2 | `Feature::define('flag', true)` в тестах без персистенции |
| **Faker-интеграция в фабриках** | ❌ | 🟡 P2 | Тесная интеграция Faker в Model Factories |

```php
// Паттерн Fake для тестирования
class NotificationFake
{
    public static function assertSentTo(mixed $notifiable, string $class): void { ... }
    public static function assertNothingSent(): void { ... }
    public static function assertCount(int $count, string $class): void { ... }
}
```

---

## 14. Модульная архитектура

**Источник: Spiral Bootloaders, FuelPHP Modules, DDD**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| Service Providers | ✅ | — | |
| **Module System** | ❌ | 🟡 P2 | Самодостаточный модуль: `modules/Blog/{routes.php, BlogServiceProvider.php, Models/, Controllers/}` |
| **Bootloaders** | ❌ | 🟡 P2 | Компактные init-классы с явными зависимостями между собой (Spiral-подход, более детальный чем Provider) |
| **Domain Events** (DDD-friendly) | ❌ | 🟡 P2 | `$model->recordEvent(new OrderPlaced($this))` — события сбрасываются при `flush()` |
| HMVC | ❌ | ⚪ P3 | Внутренние sub-запросы между контроллерами |

```
app/
├── Modules/
│   ├── Blog/
│   │   ├── BlogServiceProvider.php
│   │   ├── routes.php
│   │   ├── Models/Post.php
│   │   ├── Controllers/PostController.php
│   │   └── config/blog.php
│   └── Commerce/
│       └── ...
```

---

## 15. API-инструменты

**Источник: Laravel API Resources, Lumen, Laminas API Tools, Spiral**

| Функция | Статус | Приоритет | Описание |
|---------|--------|-----------|----------|
| GraphQL | ✅ | — | |
| **API Resources / Transformers** | ❌ | 🔴 P0 | `UserResource::make($user)` — трансформация моделей в JSON с управлением полями, `whenLoaded()`, пагинацией |
| **OpenAPI / Swagger генерация** | ❌ | 🟠 P1 | Атрибуты на контроллерах → автоматический `swagger.json` / `openapi.yaml` |
| **API Versioning Middleware** | ❌ | 🟡 P2 | Routing по `Accept: application/vnd.api.v2+json` или `/api/v2/` |
| **JSON:API формат** | ❌ | ⚪ P3 | Стандартизированный формат ответов с `links`, `included`, `errors` |

### API Resources

```php
class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'email'  => $this->when($this->isVisible('email'), $this->email),
            'posts'  => PostResource::collection($this->whenLoaded('posts')),
            'meta'   => ['created' => $this->created_at->toIso8601String()],
        ];
    }
}

// Контроллер
return UserResource::make($user);                          // один объект
return UserResource::collection(User::paginate(15));       // с пагинацией
```

---

## 16. Новые пакеты

Пакеты, которых сейчас нет в monorepo:

| Пакет | Источник идеи | Приоритет | Описание |
|-------|--------------|-----------|----------|
| `nextphp/notifications` | Laravel | 🔴 P0 | Email / DB / Slack / SMS из одного Notification-класса |
| `nextphp/translation` | Symfony, Laravel | 🔴 P0 | i18n: файловые переводы, pluralization, middleware локали |
| `nextphp/resources` | Laravel | 🔴 P0 | API Resources — трансформеры JSON-ответов |
| `nextphp/feature` | Laravel Pennant | 🟠 P1 | Feature Flags с per-user состоянием и DB/array хранилищем |
| `nextphp/lock` | Symfony | 🟠 P1 | Distributed Locks (Redis / Database / Flock) |
| `nextphp/broadcasting` | Laravel | 🟡 P2 | Real-time события клиентам через WebSocket / Pusher |
| `nextphp/socialite` | Laravel Socialite | 🟡 P2 | OAuth2 (GitHub, Google, Facebook) |
| `nextphp/openapi` | Spiral | 🟡 P2 | Генерация OpenAPI spec из атрибутов |

---

## 17. Итоговый приоритетный план

### 🔴 P0 — Реализовать в первую очередь (максимальный impact)

| # | Функция | Пакет | Почему важно |
|---|---------|-------|--------------|
| 1 | **Task Scheduler** | `nextphp/queue` расширение | Базовая возможность любого серьёзного фреймворка |
| 2 | **Model Casting** (AsEnum, AsArray, AsDatetime) | `nextphp/orm` | Убирает огромный boilerplate при работе с JSON/Enum/датами |
| 3 | **API Resources / Transformers** | `nextphp/resources` | Нужен в каждом API — контроль отдаваемых полей, пагинация |
| 4 | **Notifications** (Mail + DB канал) | `nextphp/notifications` | Единый API уведомлений — сейчас всё делается вручную |
| 5 | **Translation / i18n** | `nextphp/translation` | Без локализации нельзя делать multilingual приложения |
| 6 | **RBAC** | `nextphp/auth` расширение | Gates/Policies есть, но нет ролей с наследованием |
| 7 | **Route Model Binding** | `nextphp/routing` | Убирает `findOrFail` из каждого контроллера |
| 8 | **Flash Messages** | `nextphp/http` расширение | Стандартный паттерн для web-форм |
| 9 | **Global Scopes + Accessors/Mutators** | `nextphp/orm` | Завершает Eloquent-совместимость ORM |
| 10 | **Расширенный DebugBar** (+Cache/Events/Queue/Logs/Auth панели) | `nextphp/debugbar` | Ускоряет отладку в разы |

### 🟠 P1 — Высокий приоритет

- Compiled Container (производительность в production)
- Deferrable Service Providers
- Distributed Lock (`nextphp/lock`)
- CSRF Middleware
- Signed / Temporary URLs
- Queue Interceptors / Middleware
- OpenTelemetry Hooks
- Feature Flags (`nextphp/feature`)
- Fixture Factory для тестов
- Static Class Scanner (Tokenizer)
- Middleware Groups (global/web/api)
- Attribute Cache (Reflection results)

### 🟡 P2 — Средний приоритет

- OpenAPI генерация из атрибутов
- Data Mapper / Repository паттерн
- Module System
- Queue Dashboard (Horizon-lite)
- Monolog адаптер
- API Versioning Middleware
- Serverless / Bref конфигурация
- Broadcasting (real-time events)
- CLI Prompts (интерактивные)
- Persistence профиля DebugBar

### ⚪ P3 — Низкий приоритет

- HMVC (иерархические запросы)
- JSON:API формат
- SOAP Server
- Precognition (live-валидация)
- Micro Application mode

---

## Уникальные идеи для Nextphp (нет аналогов в других фреймворках)

Помимо заимствований — идеи, которые сделают Nextphp уникальным:

| Идея | Описание |
|------|----------|
| **AI-ассистент CLI** | `nextphp ai:generate controller "UserController with CRUD for User model"` — генерация через Claude API |
| **Compile Everything mode** | Один флаг `--compile` компилирует: контейнер + роуты + конфиги + шаблоны в PHP-файлы |
| **Zero-config mode** | Convention-over-configuration: `app/Controllers/UserController.php` → автоматически `/users` маршрут |
| **Type-safe Config DTO** | `$app->config(DatabaseConfig::class)->host` — строго типизированные конфиги без `config('db.host')` |
| **Declarative Cron** | `#[Schedule('0 2 * * *')]` атрибут на классе Job — автодискавери планировщика |
| **Built-in Health Checks** | `GET /healthz` — стандартный endpoint для k8s liveness/readiness probe |
