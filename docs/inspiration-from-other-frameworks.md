# Лучшее из других PHP-фреймворков

> Анализ 12 популярных PHP-фреймворков — что можно заимствовать или улучшить в Nextphp.
> Статус: ✅ уже есть, 💡 можно добавить, ⭐ высокий приоритет.

---

## Laravel

Один из самых популярных фреймворков — богатая экосистема и Developer Experience.

### Что уже есть в Nextphp
- Eloquent-подобный ORM с QueryBuilder, связями, scopes, observers ✅
- Artisan-подобный Console с make-генераторами ✅
- Service Providers + DI Container ✅
- Queue + Batch Jobs + Failed Jobs ✅
- Events + Listeners + Subscribers ✅
- Mail (SMTP, SES, Mailgun, Postmark) ✅
- Cache (File, Redis, Memcached, Database) ✅
- Auth (Session, Token, JWT, Gates, Policies, 2FA) ✅

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Notifications** (`nextphp/notifications`) | Единый API для уведомлений через Email, SMS, Slack, DB. Класс `Notification` с методом `via()` | ⭐ Высокий |
| **Feature Flags** (Laravel Pennant) | Декларативные feature flags: `Feature::active('new-ui')`. Позволяет A/B тесты, постепенный rollout, per-user флаги | ⭐ Высокий |
| **Eloquent API Resources** | Трансформеры для JSON API: `UserResource`, `UserCollection` с пагинацией | ⭐ Высокий |
| **Model Casting** | Автоматическое приведение типов атрибутов модели: `protected $casts = ['meta' => AsCollection::class]` | ⭐ Высокий |
| **Task Scheduler** | Единая точка для периодических заданий в коде вместо разрозненных cron-задач: `$schedule->command('report:send')->daily()` | ⭐ Высокий |
| **Flash Messages** | Typed flash сообщения с уровнями (success/error/warning/info) и persistence между редиректами | ⭐ Высокий |
| **Signed URLs** | Временные подписанные URL: `URL::temporarySignedRoute('verify', now()->addMinutes(30), ['id' => 1])` | Средний |
| **Broadcasting** | Real-time события через WebSocket/Pusher/Ably — `broadcast(new OrderShipped($order))` | Средний |
| **Telescope / Pulse** | Расширенный debugbar: трекинг запросов, очередей, исключений в реальном времени с persistence в БД | Средний |
| **CLI Prompts** | Интерактивные CLI подсказки с автодополнением, прогресс-барами, мульти-селектом | Средний |
| **Horizon-like Queue UI** | Простая веб-панель для просмотра и управления заданиями очередей, retry/backoff стратегии, rate limits, unique jobs | Средний |
| **Auth Scaffolding** | Готовые контроллеры для аутентификации (логин, регистрация, восстановление пароля, подтверждение email) | Средний |
| **Precognition** | Валидация форм на стороне сервера без полной отправки — для live-валидации во frontend | Низкий |

---

## Symfony

Компонентная архитектура — каждый пакет может использоваться отдельно.

### Что уже есть в Nextphp
- PSR-7/15 HTTP ✅
- DI Container с Compiler Passes ✅
- Console Component ✅
- Event Dispatcher (PSR-14) ✅

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Translation / i18n** | Полноценная i18n: ICU форматы, pluralization, `trans('messages.greeting', ['name' => 'Alice'])`, lazy-loading переводов | ⭐ Высокий |
| **Lock Component** | Distributed locks: `$lock = $factory->createLock('invoice-gen'); $lock->acquire()` | ⭐ Высокий |
| **Messenger** (продвинутые очереди) | Middleware pipeline для сообщений, routing по типу, транспорты (AMQP, Redis Streams, Doctrine), retry с envelope stamps | ⭐ Высокий |
| **Container Compile** | Компиляция контейнера в PHP-кэш (dumped container), "warmup" в prod — быстрый boot, меньше Reflection | ⭐ Высокий |
| **Deferrable Providers** | Провайдеры, которые грузятся только если их сервис запрашивается | ⭐ Высокий |
| **Web Profiler / Debug Toolbar** | Полноценная debug-панель: SQL queries, cache hits, events timeline, request/response, logs, memory diff | ⭐ Высокий |
| **Workflow / State Machine** | Состояния объекта, переходы, guards, события при смене состояния | Средний |
| **Serializer** | Двустороннее преобразование объектов ↔ JSON/XML/CSV с группами, версионированием | Средний |
| **Security Voters** | Granular авторизация через `VoterInterface::vote()` — гибче чем Gates для сложных ACL | Средний |
| **Form Component** | Server-side формы с автоматической валидацией, CSRF, вложенными формами, data transformers | Средний |
| **Config Component** | Структурированная конфигурация с TreeBuilder, типизацией, валидацией схемы config-файлов | Средний |
| **Monolog Integration** | Адаптер к Monolog (опционально), processors, channeling | Средний |
| **Asset Mapper** | Управление frontend-ассетами без Node.js: importmaps, CSS/JS bundling | Низкий |

---

## Yii2

Известен производительностью, встроенным RBAC и ActiveRecord.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **RBAC** (Role-Based Access Control) | Иерархические роли и разрешения: `User::can('manage-posts')`. Хранение в БД, наследование ролей | ⭐ Высокий |
| **Behavior System** | Подключаемое поведение к моделям через интерфейс: `TimestampBehavior`, `SoftDeleteBehavior` как переиспользуемые блоки | Средний |
| **GridView / DataProvider** | Абстракция источника данных для пагинированных, фильтруемых, сортируемых коллекций — полезно для API | Средний |
| **I18n Formatter** | Форматирование чисел, дат, валют по локали: `Yii::$app->formatter->asCurrency(1234.5, 'USD')` | Средний |
| **Extended Debug Toolbar** | Детальный профилировщик с SQL explain, timeline, memory diff между запросами | Средний |
| **Codegen from DB schema** | Генерация кода (model, controller) на основе существующей схемы БД (как Gii) | Средний |
| **Asset Bundle** | Декларативные зависимости CSS/JS — аналог asset manifest с автоматической минификацией | Низкий |

---

## CakePHP

Известен Convention over Configuration и ORM.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Fixture Factory** | Декларативные фикстуры для тестов через factory: `UserFactory::make(['role' => 'admin'])` | Средний |
| **Request Policy** | Политики авторизации на уровне Request, не только модели: `$this->Authorization->authorize($request)` | Средний |
| **Bake** (расширенный codegen) | Генерация полного CRUD (Controller + Views + Tests) из схемы БД одной командой | Средний |
| **Table Associations Eager Loading** | Автоматическое обнаружение и загрузка связей без явного `with()` на основе конвенций имён | Низкий |

---

## Slim

Микрофреймворк — минимализм и PSR-совместимость.

### Что уже есть в Nextphp
- PSR-7/15 полностью ✅
- Middleware Pipeline ✅

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Micro Application mode** | Минимальный bootstrap (fast path), минимальные зависимости, "single-file app" — для быстрых маленьких сервисов | Средний |
| **Deferred Resolution** | Lazy-resolve контроллеров из контейнера только при matched route, не при регистрации | Средний |
| **RouteCollector Interface** | Выделенный интерфейс для регистрации маршрутов — позволяет менять реализацию роутера без изменения кода | Низкий |

---

## Phalcon

Написан на C как PHP-расширение — экстремальная производительность.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Compiled Annotations Cache** | Кэшировать результаты парсинга `#[Attribute]` / PHPDoc в APC/файл — не перечитывать Reflection при каждом запросе | ⭐ Высокий |
| **Preloading / OPcache-friendly** | Preloading список классов, "compiled" routes/container, минимизация reflection | ⭐ Высокий |
| **Micro Application** | Ультра-лёгкий режим без лишних компонентов для serverless/edge функций | Средний |
| **Assets Manager** | Минификация, объединение, версионирование CSS/JS файлов | Низкий |

---

## CodeIgniter

Известен простотой и малым размером.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Throttler** (Token Bucket) | Более гибкий rate limiting через Token Bucket алгоритм — дополнение к Sliding Window | Средний |
| **Debug Toolbar Hooks** | Хуки в цикл запроса для измерения каждой фазы: routing, controller, view, db | Средний |
| **Publisher** | Копирование файлов пакетов в public/ при установке (fonts, JS, CSS vendored assets) | Низкий |

---

## Laminas (Zend Framework)

Корпоративный фреймворк — строгая архитектура, много PSR.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Hydrators** | Двустороннее преобразование массив ↔ объект: `ClassMethods`, `ArraySerializable`, `ReflectionHydrator` | ⭐ Высокий |
| **Input Filter** | Многослойная фильтрация и валидация входных данных (отдельно от Validator) с chain-of-filters | Средний |
| **Service Manager** (расширенный) | Делегаторы (Delegator Factories), абстрактные фабрики, инициализаторы — паттерны для сложного DI | Средний |
| **API Tools** (HAL/JSON:API) | Генерация HAL-compliant или JSON:API ответов с links, embedded resources, пагинацией | Средний |
| **SOAP Server** | Встроенная поддержка SOAP веб-сервисов с WSDL генерацией | Низкий |

---

## Lumen

Micro Laravel — урезанная версия для API.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **API Versioning Middleware** | Автоматический routing по версии API через заголовок `Accept: application/vnd.api.v2+json` | Средний |
| **Response Macros** | Расширяемые помощники ответа: `Response::macro('xml', fn($data) => ...)` | Низкий |

---

## FuelPHP

Известен HMVC и модульной архитектурой.

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Module System** | Самодостаточные модули со своими роутами, контроллерами, моделями, конфигом | Средний |
| **HMVC** (Hierarchical MVC) | Вызов одного контроллера из другого внутри запроса: `Request::forge('widget/sidebar')->execute()` | Низкий |

---

## Spiral Framework

Современный PHP фреймворк с RoadRunner и компонентной архитектурой.

### Что уже есть в Nextphp
- RoadRunner / Swoole через Octane ✅
- Fiber-based async ✅

### Что можно добавить

| Функция | Описание | Приоритет |
|---------|----------|-----------|
| **Tokenizer / Static Analysis** | Статическое сканирование классов по атрибутам/интерфейсам без загрузки — быстрое обнаружение Routes, Listeners | ⭐ Высокий |
| **Queue Interceptors** | Middleware для jobs: трассировка, retry-политики, dead-letter routing через chain of interceptors | ⭐ Высокий |
| **Bootloaders** | Компактные init-классы: каждый загружает одну фичу и объявляет зависимости — точнее чем ServiceProvider | Средний |
| **RoadRunner/FrankenPHP worker mode** | State reset hooks, memory leak guards, request-scoped services, поддержка FrankenPHP | Средний |
| **Cycle ORM DataMapper** | DataMapper паттерн в дополнение к ActiveRecord: Entity → отдельно от logic, Repository как единственная точка доступа | Средний |
| **GRPC Server** | Встроенная поддержка gRPC через RoadRunner: `#[GRPC\Service]`, proto-based контракты | Средний |
| **Scaffolding DSL** | Генерация кода через DeclarationRegistry: добавлять методы, свойства к существующим классам | Низкий |

---

## ORM / База данных (общее)

| Функция | Описание | Источник | Приоритет |
|---------|----------|----------|-----------|
| **Soft Deletes** | `SoftDeletes` trait прямо в ядре ORM — очень востребованная функция | Laravel | ⭐ Высокий |
| **Global Scopes** | Автоматическая фильтрация запросов: `where('is_active', true)` | Laravel/Yii2 | ⭐ Высокий |
| **Model Casting** | Автоматическое приведение типов: AsCollection, AsEnum, JSON, datetime | Laravel | ⭐ Высокий |
| **Migrations UX** | `migrate:fresh`, `migrate:refresh`, `migrate:status`, seed hooks | Laravel | ⭐ Высокий |
| **Database Fixtures** | Фикстуры с быстрым наполнением тестовой БД + транзакционный режим | CakePHP/Symfony | Средний |
| **Entity/Repository** | Альтернативный слой "entity mapping" без Active Record (для больших проектов) | Doctrine/Symfony | Средний |
| **Metadata Cache** | Агрессивные кэши метаданных, генерация классов/проксей для моделей | Phalcon | Средний |

---

## Routing / HTTP (общее)

| Функция | Описание | Источник | Приоритет |
|---------|----------|----------|-----------|
| **Route Model Binding** | Автоматическое преобразование `{id}` → `Model`, с кастомизацией | Laravel | ⭐ Высокий |
| **OpenAPI / Swagger** | Генерация OpenAPI-спецификации по атрибутам/DTO, автоген для клиента/валидаторов | Spiral | ⭐ Высокий |
| **Named params + requirements** | Regex-ограничения параметров (`{id<\d+>}`), host-based routing, locale-prefix | Symfony | Средний |
| **Vite / Asset helpers** | Helpers `vite()` для вставки ассетов, HMR detection, manifest reading | Laravel Mix | Средний |

---

## Security / Auth (общее)

| Функция | Описание | Источник | Приоритет |
|---------|----------|----------|-----------|
| **RBAC** | Иерархические роли и разрешения с хранением в БД | Yii2 | ⭐ Высокий |
| **CSRF / Rate Limiting** | CSRF middleware, rate limiters с storage-адаптерами, сессии с encrypt/sign | Laravel | ⭐ Высокий |
| **Guard / Authenticators** | Firewall-like конфигурация, remember-me | Symfony | Средний |
| **Security Voters** | Granular ACL через `VoterInterface::vote()` | Symfony | Средний |

---

## Observability / DX (общее)

| Функция | Описание | Источник | Приоритет |
|---------|----------|----------|-----------|
| **OpenTelemetry hooks** | OTel hooks для http/db/cache/queue | Общее | Средний |
| **Cache tagging** | Теги для Redis/File, `remember()` | Laravel | Средний |
| **Event autodiscovery** | Autodiscovery listeners/subscribers по атрибутам/конвенциям | Symfony/Laravel | Средний |
| **Serverless / Bref** | Оптимизации и инструкции для AWS Lambda через Bref | Octane | Средний |

---

## Сводная таблица приоритетов

### ⭐ Высокий приоритет (реализовать в первую очередь)

| Функция | Источник | Пакет |
|---------|----------|-------|
| **Notifications** (Email, SMS, Slack, DB) | Laravel | `nextphp/notifications` |
| **Feature Flags** | Laravel Pennant | `nextphp/feature` |
| **API Resources / Transformers** | Laravel | `nextphp/resources` |
| **Model Casting** (AsCollection, AsEnum и др.) | Laravel | `nextphp/orm` |
| **Soft Deletes + Global Scopes** | Laravel | `nextphp/orm` |
| **Task Scheduler** | Laravel | `nextphp/scheduling` |
| **RBAC** (роли + разрешения + иерархия) | Yii2 | `nextphp/auth` |
| **Compiled Attribute Cache** | Phalcon | `nextphp/core` |
| **Preloading / OPcache-friendly** | Phalcon | `nextphp/core` |
| **Flash Messages** | CakePHP | `nextphp/http` |
| **Distributed Lock** | Symfony | `nextphp/lock` |
| **Translation / i18n** | Symfony | `nextphp/translation` |
| **Container Compile + warmup** | Symfony | `nextphp/core` |
| **Web Profiler / Debug Toolbar** | Symfony | `nextphp/debugbar` |
| **OpenAPI / Swagger генерация** | Spiral | `nextphp/openapi` |
| **Hydrators** (array ↔ object) | Laminas | `nextphp/serializer` |
| **Deferrable Providers** | Lumen | `nextphp/core` |
| **Tokenizer** (Static class scanner) | Spiral | `nextphp/core` |
| **Queue Interceptors** | Spiral | `nextphp/queue` |
| **Messenger** (продвинутые очереди) | Symfony | `nextphp/queue` |
| **Route Model Binding** | Laravel | `nextphp/routing` |
| **Migrations UX** (`fresh`, `refresh`, `status`) | Laravel | `nextphp/orm` |

### Средний приоритет

| Функция | Источник |
|---------|----------|
| Workflow / State Machine | Symfony |
| Symfony Serializer | Symfony |
| Broadcasting (WebSocket events) | Laravel |
| CLI Prompts (интерактивные) | Laravel |
| Security Voters | Symfony |
| API Versioning Middleware | Lumen |
| GRPC Server | Spiral |
| Behavior System (ORM) | Yii2 |
| Micro Application (serverless mode) | Phalcon/Slim |
| Bootloaders | Spiral |
| FrankenPHP adapter | Spiral/Octane |
| Horizon-like Queue UI | Laravel |
| Database Fixtures | CakePHP/Symfony |
| OpenTelemetry hooks | Общее |
| Monolog Integration | Symfony |
| Auth Scaffolding | Laravel |
| Cache tagging | Laravel |
| Codegen from DB schema | Yii2/CakePHP |

### Низкий приоритет

| Функция | Источник |
|---------|----------|
| SOAP Server | Laminas |
| HMVC | FuelPHP |
| Asset Bundle / Manager | Yii2, Phalcon |
| Bake (полный CRUD codegen) | CakePHP |
| HAL / JSON:API | Laminas |
| Scaffolding DSL | Spiral |
| Response Macros | Lumen |
| RouteCollector Interface | Slim |

---

## Детальные предложения по реализации

### 1. `nextphp/notifications` — Уведомления

```php
use Nextphp\Notifications\Notification;
use Nextphp\Notifications\Channels\MailChannel;
use Nextphp\Notifications\Channels\SlackChannel;
use Nextphp\Notifications\Channels\DatabaseChannel;

class OrderShipped extends Notification
{
    public function __construct(private Order $order) {}

    public function via(mixed $notifiable): array
    {
        return [MailChannel::class, DatabaseChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your order has shipped!')
            ->line("Order #{$this->order->id} is on its way.")
            ->action('Track Order', url("/orders/{$this->order->id}"));
    }

    public function toDatabase(mixed $notifiable): array
    {
        return ['order_id' => $this->order->id, 'status' => 'shipped'];
    }
}

// Использование
$user->notify(new OrderShipped($order));

// Через фасад
Notification::send($users, new OrderShipped($order));
```

### 2. `nextphp/feature` — Feature Flags

```php
use Nextphp\Feature\Feature;

// Определение флага
Feature::define('new-checkout', fn(User $user) => $user->isInBetaGroup());
Feature::define('dark-mode', true); // всем включено

// Проверка
if (Feature::active('new-checkout')) {
    return $this->newCheckout();
}

// Декоратор для маршрутов
$router->get('/checkout', [CheckoutController::class, 'new'])
       ->feature('new-checkout'); // 404 если флаг выключен

// Хранение состояния в БД (per-user)
Feature::for($user)->active('new-checkout');
Feature::for($user)->deactivate('new-checkout');
```

### 3. `nextphp/translation` — Интернационализация

```php
use Nextphp\Translation\Translator;

$translator = new Translator(defaultLocale: 'ru');
$translator->load('ru', __DIR__ . '/lang/ru.php');

// Простой перевод
echo $translator->trans('auth.login'); // "Войти"

// С параметрами
echo $translator->trans('welcome.message', ['name' => 'Алиса']);
// "Добро пожаловать, Алиса!"

// Pluralization
echo $translator->trans('items.count', ['count' => 5]);
// ru: "5 предметов" | en: "5 items"

// Смена локали
$translator->setLocale('en');
```

### 4. `nextphp/lock` — Distributed Locks

```php
use Nextphp\Lock\LockFactory;
use Nextphp\Lock\Store\RedisLockStore;
use Nextphp\Lock\Store\DatabaseLockStore;

$factory = new LockFactory(new RedisLockStore($redis));

$lock = $factory->create('invoice:generate:42', ttl: 30);

if ($lock->acquire()) {
    try {
        generateInvoice(42);
    } finally {
        $lock->release();
    }
}

// Блокирующий режим (ждать до X секунд)
$lock->acquire(blocking: true, timeout: 5);

// Через callback
$lock->run(fn() => generateInvoice(42));
```

### 5. RBAC в `nextphp/auth`

```php
use Nextphp\Auth\Rbac\RbacManager;

// Определение
$rbac = new RbacManager($store);
$rbac->createPermission('post.create');
$rbac->createPermission('post.delete');
$rbac->createRole('editor', permissions: ['post.create']);
$rbac->createRole('admin', inherits: ['editor'], permissions: ['post.delete']);

// Назначение пользователю
$rbac->assign('admin', userId: 1);

// Проверка
if ($rbac->can($user->id, 'post.delete')) {
    $post->delete();
}

// В Gate
$gate->define('post.delete', fn(User $u, Post $p) => $rbac->can($u->id, 'post.delete'));
```

### 6. API Resources в `nextphp/resources`

```php
use Nextphp\Resources\JsonResource;
use Nextphp\Resources\ResourceCollection;

class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'links' => [
                'self' => url("/users/{$this->id}"),
            ],
        ];
    }
}

// В контроллере
return UserResource::make($user);           // один объект
return UserResource::collection($users);    // коллекция с мета-данными
return UserResource::collection($paginator); // с пагинацией
```

### 7. Task Scheduler в `nextphp/scheduling`

```php
use Nextphp\Scheduling\Schedule;

// В сервис-провайдере
$schedule->command('reports:daily')->dailyAt('08:00');
$schedule->command('cache:clear')->hourly();
$schedule->job(new ProcessMetrics())->everyFiveMinutes();
$schedule->call(fn() => DB::table('logs')->where('created_at', '<', now()->subDays(30))->delete())
         ->weekly();

// Условия
$schedule->command('backups:run')
         ->daily()
         ->environments(['production'])
         ->withoutOverlapping();
```

### 8. Soft Deletes в `nextphp/orm`

```php
use Nextphp\Orm\Concerns\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;
}

// Использование
$post->delete();              // мягкое удаление (устанавливает deleted_at)
Post::withTrashed()->get();   // включая удалённые
Post::onlyTrashed()->get();   // только удалённые
$post->restore();             // восстановление
$post->forceDelete();         // физическое удаление
```

---

## Итог

Nextphp уже покрывает **~85% функциональности** среднего PHP-фреймворка.
Наиболее ценные добавления (в порядке impact/effort):

1. **Notifications** — очень востребовано, сейчас нет единого API
2. **Translation / i18n** — без этого нельзя сделать multilingual app
3. **RBAC** — Gate/Policy есть, но нет ролей с наследованием
4. **API Resources** — трансформеры нужны в любом API
5. **Feature Flags** — стандарт для современных продуктов
6. **Task Scheduler** — функциональность, которую ждут от серьёзного фреймворка
7. **Distributed Lock** — нужен для очередей, cron, финансов
8. **Web Profiler** — сделает DX сопоставимым с Symfony
9. **OpenAPI генерация** — мощное преимущество для API-first проектов
10. **Soft Deletes + Model Casting** — базовые, но очень ожидаемые функции ORM
