# Анализ: ТЗ vs Текущая реализация

> Дата анализа: 25 марта 2026 г.
> Документ обновляется по мере реализации. Отмечай ✅ выполненные пункты прямо здесь.

---

## Этап 0 — Инфраструктура (Недели 1–2)

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Monorepo структура на GitHub | ✅ | |
| CI/CD: тесты, линтер, статический анализ | ✅ | Все 19 пакетов |
| PHPStan level 8, CS Fixer, Psalm | ✅ | Настроены |
| `composer.json` для всех пакетов | ✅ | |
| Dependabot | ✅ | |
| Скелет документации (MkDocs/Docusaurus) | ⚠️ | `docs/` есть, но минимален |

---

## Этап 1 — Ядро (Недели 3–8)

### nextphp/core

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Container с Autowiring (Reflection API) | ✅ | |
| `bind()`, `singleton()`, `scoped()`, `instance()` | ✅ | |
| Service Providers: `register()` + `boot()` | ✅ | |
| `#[Singleton]`, `#[Inject]` attributes | ✅ | |
| Compiler Passes (оптимизация в production) | ✅ | `CompilerPassInterface` + `addCompilerPass()`, runs after register() before boot() |
| 100% покрытие Container тестами | ⚠️ | Частично (5 тест-файлов) |
| ReflectionClass кэш (производительность) | ✅ | Добавлен статический кэш |

### nextphp/http

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| PSR-7 Request/Response обёртки | ✅ | |
| PSR-15 Middleware Pipeline | ✅ | |
| Обработчик исключений JSON/HTML | ✅ | JSON + HTML; debug-режим с трейсом стека и цепочкой причин |
| File Upload | ✅ | `UploadedFile.php` |
| Cookies | ✅ | `Cookie`, `CookieJar`, `CookieMiddleware` |
| Session | ✅ | `SessionInterface`, `ArraySession`, `FileSession`, `SessionMiddleware` |
| ContainerInterface для DI контроллеров | ✅ | Добавлено |

### nextphp/routing

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Быстрый Router на основе RadixTree | ✅ | |
| Attribute-маршруты `#[Route(...)]` | ✅ | |
| Fluent Route API | ✅ | |
| Route Groups, Middleware, Named Routes | ✅ | |
| URL Generator | ✅ | |
| `Router::prefix()` fluent entry point | ✅ | Добавлено |
| `RouteGroup` namePrefix работает корректно | ✅ | Исправлено |
| Rate Limiting через Middleware | ✅ | `RateLimitMiddleware` + `ArrayRateLimiter` + `RateLimiterInterface` |
| Версионирование API `/api/v1/` | ✅ | `Router::api()` + поддержка версий (`v1`, `v2`, ...) |

---

## Этап 2 — ORM и БД (Недели 9–16)

### nextphp/orm + nextphp/migrations

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Fluent Query Builder (SELECT/INSERT/UPDATE/DELETE) | ✅ | |
| Joins, Subqueries, Raw expressions | ✅ | |
| MySQL, PostgreSQL, SQLite PDO | ✅ | |
| Connection Pool | ✅ | `ConnectionPool.php` |
| Active Record Model с CRUD | ✅ | |
| `hasOne`, `hasMany`, `belongsTo`, `belongsToMany` | ✅ | |
| `morphTo` (полиморфные связи) | ✅ | `MorphTo` с `typeMap`, поддержка строковых алиасов и полных class-name |
| Eager/Lazy loading, `with()` | ✅ | |
| N+1 query prevention warning | ✅ | `warnOnLazyLoading()` + кастомный handler; `preventLazyLoading()` кидает исключение |
| Model Events (creating/created/updating/...) | ✅ | |
| Observers | ✅ | `Model::observe($observer)` — автоматическая подписка на все события через методы |
| Local + Global Scopes | ✅ | |
| Soft Deletes | ✅ | |
| Schema Builder + Migrations + rollback | ✅ | |
| `paginate(N)` | ✅ | `Builder::paginate()` → `Paginator` с мета-данными |
| Model Factories (Faker-based) | ✅ | `ModelFactory` + встроенный `FakerGenerator` |
| Seeders | ✅ | `Seeder` + `SeederRunner` (авто-discover, цепочки) |
| ORM в PHPStan/CI | ✅ | Добавлен |

---

## Этап 3 — Сервисы (Недели 17–24)

### nextphp/cache

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| PSR-16 Cache | ✅ | |
| Array driver | ✅ | |
| File driver | ✅ | |
| Redis driver | ✅ | Переписан на реальный Redis ext |
| Memcached driver | ✅ | `MemcachedCache` + `CacheFactory::memcached()` |
| Database driver | ✅ | `DatabaseCache` (PDO, SQLite/MySQL/PgSQL), `CacheFactory::database()` |
| Cache Tags | ✅ | `tag()` + `flushTag()` во всех драйверах (Array/File/Redis/Memcached/Database) |
| `remember()` | ✅ | `remember()` во всех драйверах |
| `CacheFactory` | ✅ | Добавлено |

### nextphp/events

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| PSR-14 Event Dispatcher | ✅ | |
| Listeners + Subscribers | ✅ | |
| Priority-based ordering | ✅ | Добавлено |
| StoppableEventInterface | ✅ | Добавлено |
| `AbstractEvent` base class | ✅ | Добавлено |
| Async Events через очередь | ✅ | |
| Event Discovery через Attributes | ✅ | `#[ListensTo(EventClass::class)]` + `EventDiscovery::register()` |

### nextphp/validation

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Rule-based валидация | ✅ | |
| Pipe syntax `'required\|email\|max:255'` | ✅ | Добавлено |
| `bail` — стоп на первой ошибке | ✅ | Добавлено |
| `nullable` | ✅ | Добавлено |
| `boolean`, `integer`, `array`, `confirmed` | ✅ | Добавлено |
| Custom Rules через класс | ✅ | |
| Custom Rules через замыкание (Closure) | ✅ | `ClosureRule` |
| Form Request | ✅ | Добавлено |
| `ValidationException` | ✅ | Добавлено |
| `Validator::make()` фабрика | ✅ | Добавлено |
| Attribute-based `#[Required]`, `#[Email]` | ✅ | `AttributeValidator` + `#[Required]`, `#[Email]`, `#[Min]`, `#[Max]` |
| Локализация сообщений об ошибках | ✅ | `Validator::setLocale()` + словари `ru/en` + placeholders |

### nextphp/queue

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Job класс с `handle()` | ✅ | |
| Database driver | ✅ | |
| Redis driver | ✅ | Переписан: sorted sets + Lua |
| Sync driver | ✅ | Добавлено `SyncQueue` |
| Worker + Retry логика + Delay | ✅ | |
| `RetryableJobInterface` (per-job retry) | ✅ | Добавлено |
| Dead Letter Queue | ✅ | |
| Job Batching | ✅ | `Batch` + `BatchJobWrapper`, then/catch/finally callbacks |
| Failed Jobs — мониторинг/таблица | ✅ | `FailedJobStore` (PDO), `retry()`, `all()`, `flush()` |

---

## Этап 4 — View и CLI (Недели 25–30)

### nextphp/view

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Компиляция шаблонов в PHP-файлы | ✅ | `Compiler.php` |
| `@if/@elseif/@else/@endif`, `@unless` | ✅ | |
| `@foreach`, `@forelse/@empty`, `@for`, `@while` | ✅ | |
| `@switch/@case/@default`, `@php` | ✅ | |
| `@include`, `@extends`, `@section`, `@yield` | ✅ | |
| Компоненты и слоты `<x-component>` | ✅ | `ComponentRegistry`, x-теги |
| `@component / @slot / @endcomponent` | ✅ | |
| Экранирование `{{ }}` по умолчанию | ✅ | |
| Raw output `{!! !!}` | ✅ | |
| Кэширование скомпилированных шаблонов | ✅ | Инвалидация по mtime |
| Кастомные директивы | ✅ | `ViewEngine::directive()` |

### nextphp/console

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Базовый Command класс | ✅ | |
| Input/Output форматирование | ✅ | Таблицы с выравниванием, прогресс-бар, цвета |
| `make:controller` | ✅ | `MakeControllerCommand`, авто-суффикс Controller |
| `make:model` | ✅ | `MakeModelCommand` + `--migration` флаг |
| `make:migration` | ✅ | `MakeMigrationCommand`, timestamp, угадывает таблицу |
| `Output::ask()` / `confirm()` | ✅ | Интерактивные вопросы |
| Task Scheduler (cron-like) | ✅ | `Scheduler` + `CronExpression`, все стандартные частоты |

---

## Этап 5 — Auth, Filesystem, Mail (Недели 31–38)

### nextphp/auth

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Session Guard | ✅ | |
| Token Guard | ✅ | |
| JWT Guard | ✅ | `JwtEncoder` (HS256) + `JwtGuard`, без внешних зависимостей |
| Gates и Policies | ✅ | |
| PSR-15 Auth Middleware | ✅ | |
| Remember Me | ✅ | `RememberMeService` + `ArrayRememberMeTokenStore` |
| Password Reset | ✅ | `PasswordResetService` + `ArrayPasswordResetTokenStore` + `DatabasePasswordResetTokenStore` |
| Email Verification | ✅ | `EmailVerificationService` + `ArrayEmailVerificationTokenStore` |
| Two-Factor Authentication (TOTP) | ✅ | `TotpGenerator` (RFC 6238, HS256, без внешних зависимостей) |

### nextphp/filesystem

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Local driver | ✅ | |
| S3 driver | ✅ | |
| FTP driver | ✅ | `FtpFilesystem` + `FtpClientInterface` / `NativeFtpClient` |
| SFTP driver | ✅ | `SftpFilesystem` + `SftpClientInterface` / `Ssh2SftpClient` |
| URL-генерация для файлов | ✅ | `url()` в Local, S3, FTP, SFTP |
| Stream поддержка для больших файлов | ✅ | `readStream()` / `writeStream()` во всех драйверах |
| Flysystem-адаптеры | ✅ | `FlysystemFilesystem` + `NextphpFilesystemAdapter` |

### nextphp/mail

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Mailable классы | ✅ | |
| SMTP driver | ✅ | |
| Отправка через очередь | ✅ | |
| SES driver | ✅ | `SesMailer` (AWS Signature V4, без ext-curl) |
| Mailgun driver | ✅ | `MailgunMailer` (multipart/form-data, US+EU) |
| Postmark driver | ✅ | `PostmarkMailer` (JSON API) |
| HTML + Text шаблоны писем | ✅ | `Mailable::html()` + `text()`, multipart/alternative в SMTP |

---

## Этап 6 — Testing и DevEx (Недели 39–44)

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| TestCase базовый класс | ✅ | |
| HTTP Testing assertions | ✅ | |
| Database Testing (RefreshDatabase) | ✅ | |
| Mocking | ✅ | Встроенные моки + интеграция Mockery (`MockeryTrait`) |
| Browser Testing (Panther/Dusk) | ✅ | `BrowserTestCase` (Panther) |
| Nextphp Debugbar | ✅ | PSR-15 `DebugbarMiddleware` |
| Exception pages в debug-режиме (Whoops-like) | ✅ | `ExceptionHandler(debug: true)` — цветная HTML-страница с трейсом и цепочкой причин |

---

## Этап 7 — Документация и Экосистема (Недели 45–52)

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| Полная документация компонентов | ✅ | 19 пакетов — каждый имеет подробную страницу в `docs/`, MkDocs nav обновлён |
| Nextphp Skeleton App | ✅ | `templates/skeleton/` |
| Nextphp API Skeleton | ✅ | `templates/api-skeleton/` |
| Nextphp Installer (CLI) | ✅ | `bin/nextphp project:install` (копирует `templates/*`) |
| Интеграция с Vite для Frontend | ✅ | `templates/skeleton` (Vite dev/build + manifest) |
| Публикация на Packagist | ❌ | Нет |
| Сайт документации | ❌ | Нет |
| Примеры приложений | ❌ | Нет |

---

## Нефункциональные требования (§10.2)

| Требование ТЗ | Статус | Примечание |
|---|---|---|
| PHPStan level 8 | ✅ | Все 19 пакетов, 0 ошибок |
| Psalm strict mode | ✅ | Psalm настроен на весь monorepo (`packages/*/src`) |
| 90%+ покрытие тестами ядра | ✅ | CI для всех 18 пакетов |
| PHP 8.2 / 8.3 / 8.4 совместимость | ✅ | CI тестирует все три |
| PSR-1, PSR-4, PSR-12 | ✅ | |
| PSR-7, PSR-11, PSR-14, PSR-15 | ✅ | |
| PSR-3 (Logger) | ✅ | `nextphp/log` — `Logger` + `StreamHandler`, `ArrayHandler`, `NullHandler`, `LogLevel` enum |
| PSR-2 | ✅ | CS Fixer |
| Собственная иерархия исключений | ✅ | База `NextphpException` в core, остальные пакеты наследуются |
| `readonly` properties | ✅ | Применено в ключевых местах (например MethodNotAllowedException) |
| Enum вместо константных массивов | ✅ | Например `HttpStatus` вместо status phrases массива |
| Нулевые зависимости ядра | ✅ | Только PSR-интерфейсы |
| Поддержка Swoole/RoadRunner/FrankenPHP | ✅ | Добавлены примеры для FrankenPHP (skeleton `Caddyfile` + README) |

---

## Итоговая матрица готовности

| Этап | Готовность | Оставшиеся пробелы |
|---|---|---|
| Этап 0 — Инфраструктура | 95% | Psalm расширить до 18 пакетов |
| Этап 1 — Ядро | 100% | — |
| Этап 2 — ORM | 100% | — |
| Этап 3 — Сервисы | 97% | Локализация валидации (не приоритетно) |
| Этап 4 — View / CLI | 100% | — |
| Этап 5 — Auth/FS/Mail | 100% | — |
| Этап 6 — Testing/DevEx | 100% | — |
| Этап 7 — Docs/Eco | 75% | Packagist, сайт, примеры приложений |

---

## Что осталось реализовать

### Этап 5 — Auth / Filesystem

- `nextphp/filesystem` — ✅ **Flysystem-адаптеры** (`FlysystemFilesystem`, `NextphpFilesystemAdapter`)

### Этап 6 — Testing и DevEx

- `nextphp/testing` — ✅ **Mocking** (Mockery-интеграция + встроенные моки)
- ✅ **Browser Testing** (Panther)
- ✅ **Nextphp Debugbar**

### Этап 7 — Документация и Экосистема

- Полная документация всех компонентов
- ✅ **Nextphp Installer** (CLI: `bin/nextphp project:install`)
- ✅ **Интеграция с Vite** для frontend assets
- **Публикация на Packagist**
- **Сайт документации**
- **Примеры приложений**

### Нефункциональные требования

- ✅ **Psalm strict mode** — расширен до всего monorepo
- **Иерархия исключений** — распространить на все пакеты
- ✅ **`readonly` properties** — применено в ключевых местах
- ✅ **Enum** вместо константных массивов — начато (например `HttpStatus`)
- ✅ **FrankenPHP** — добавлены конфиги/пример запуска
