# Идеи из Yii2 / Laravel / Symfony / CakePHP / Zend / CodeIgniter / Slim / Phalcon / FuelPHP / Laminas / Lumen / Spiral

Цель: собрать **практичные улучшения для Nextphp**, заимствуя сильные стороны известных PHP-фреймворков, но адаптируя их под текущую архитектуру (PSR-7/15, monorepo, пакеты).

---

## Движок приложения и контейнер (DI)

- **Service Container “автовайринг + compile” как в Symfony**
  - **Что добавить**: компиляция контейнера в PHP-кэш (dumped container), “warmup” в prod.
  - **Зачем**: быстрый boot, меньше Reflection в production.

- **Environment & config layering как в Laravel/Symfony**
  - **Что добавить**: единый `config/` слой с merge (default → env → local), поддержка `.env` (через лёгкий парсер без тяжёлых зависимостей).
  - **Зачем**: предсказуемая конфигурация и простая доставка.

- **Framework Kernel / HTTP Kernel как в Laravel**
  - **Что добавить**: декларативный pipeline middleware: global → group → route, aliases, priorities.
  - **Зачем**: понятное управление middleware и порядок выполнения.

- **PSR-11/Container contracts как в Laminas**
  - **Что добавить**: “factory” слой (service manager pattern) для сложных сервисов + scoped контейнеры.
  - **Зачем**: проще управлять зависимостями без магии.

---

## Routing и HTTP

- **Route Model Binding как в Laravel**
  - **Что добавить**: автоматическое преобразование `{id}` → `Model`, с кастомизацией (по полю, через resolver).
  - **Зачем**: меньше ручного `findOrFail`, чище контроллеры.

- **Named params + requirements как в Symfony**
  - **Что добавить**: regex-ограничения параметров (`{id<\d+>}`), host-based routing, locale-prefix.
  - **Зачем**: безопаснее маршруты, меньше 404/ошибок.

- **OpenAPI-first подход как в Spiral**
  - **Что добавить**: генерация OpenAPI по атрибутам/DTO, автоген для клиента/валидаторов.
  - **Зачем**: согласованность API и быстрая интеграция.

- **Микро-режим как Slim/Lumen**
  - **Что добавить**: минимальный bootstrap (fast path), минимальные зависимости, “single-file app” опционально.
  - **Зачем**: быстрые маленькие сервисы.

---

## Middleware / Filters / Interceptors

- **Filters как в Yii2 и CakePHP**
  - **Что добавить**: before/after фильтры на контроллер/экшен, сопоставление правил доступа.
  - **Зачем**: удобно для auth/логирования/кэша.

- **Event subscribers как Symfony**
  - **Что добавить**: единый event-bus на уровне HTTP lifecycle (request/response/exception).
  - **Зачем**: расширяемость без правок ядра.

---

## ORM, БД и миграции

- **Query Builder/ORM ergonomics как Laravel Eloquent**
  - **Что добавить**: глобальные “scopes”, касты/мутации атрибутов (casts), `appends`, “accessors/mutators”.
  - **Зачем**: удобнее доменная модель.

- **Entity/Repository стиль как Doctrine/Symfony (опционально)**
  - **Что добавить**: альтернативный слой “entity mapping” без Active Record (для больших проектов).
  - **Зачем**: отделение persistence от модели.

- **Migrations UX как Laravel**
  - **Что добавить**: `migrate:fresh`, `migrate:refresh`, `migrate:status`, seed hooks.
  - **Зачем**: стандартные dev-флоу без ручной рутины.

- **Database fixtures как CakePHP/Symfony**
  - **Что добавить**: фикстуры с быстрым наполнением тестовой БД + транзакционный режим.
  - **Зачем**: быстрые интеграционные тесты.

- **Performance идеи из Phalcon**
  - **Что добавить**: агрессивные кэши метаданных, генерация классов/проксей для моделей.
  - **Зачем**: ускорение без ext-подхода.

---

## Validation и Forms

- **Form Model / Form Request как Laravel + Yii2**
  - **Что добавить**: единый слой “request DTO + rules + authorize + messages() + attributes()”, авто-инъекция в handler.
  - **Зачем**: валидирование и авторизация рядом, меньше шума в контроллерах.

- **Constraint-based валидация как Symfony Validator**
  - **Что добавить**: набор “constraints” (NotBlank, Length, Choice…), валидация объектов/графа объектов.
  - **Зачем**: мощнее и структурнее для сложных DTO.

- **i18n формат сообщений как Laravel**
  - **Что добавить**: pluralization, контекстные варианты, user-friendly field names.
  - **Зачем**: лучше UX ошибок.

---

## Security

- **Security component как Symfony**
  - **Что добавить**: Guard/authenticators, voters, firewall-like конфигурация, remember-me.
  - **Зачем**: унифицированная модель безопасности.

- **CSRF / Rate limiting / Sessions как Laravel**
  - **Что добавить**: CSRF middleware, rate limiters с storage-адаптерами, сессии с encrypt/sign.
  - **Зачем**: безопасные дефолты.

---

## View / Frontend

- **Twig-подобный режим как Symfony (опционально)**
  - **Что добавить**: альтернативный renderer/адаптер к Twig (если нужен), либо совместимость по директивам.
  - **Зачем**: проще миграции и привычный стек.

- **Asset pipeline как Laravel Mix/Vite**
  - **Что добавить**: helpers `vite()` для вставки ассетов, HMR detection, manifest reading.
  - **Зачем**: консистентный фронтенд в шаблонах.

---

## Console / DX

- **Artisan-опыт как Laravel**
  - **Что добавить**: генераторы для всех пакетов (middleware, job, event, listener, policy, migration), интерактивные мастера.
  - **Зачем**: скорость разработки.

- **MakerBundle-подход как Symfony**
  - **Что добавить**: “make:*” команды с умными дефолтами + автоподключение в контейнер/роуты.
  - **Зачем**: меньше ручной склейки.

- **Profiler/Debug toolbar как Symfony/Laravel Debugbar**
  - **Что добавить**: полноценная debug-панель: SQL queries, cache hits, events timeline, request/response, logs.
  - **Зачем**: быстрее отладка, меньше “чёрных ящиков”.

---

## Logging / Observability

- **Monolog integration как Symfony**
  - **Что добавить**: адаптер к Monolog (опционально), processors, channeling.
  - **Зачем**: стандартная экосистема логов.

- **Tracing hooks**
  - **Что добавить**: OpenTelemetry hooks для http/db/cache/queue.
  - **Зачем**: наблюдаемость в проде.

---

## Caching / Queue / Events

- **Cache tagging как Laravel**
  - **Что добавить**: теги для Redis/File (насквозь), `remember()` везде.
  - **Зачем**: удобная инвалидация.

- **Queue UX как Laravel**
  - **Что добавить**: horizon-like мониторинг (базовый), retry/backoff стратегии, rate limits, unique jobs.
  - **Зачем**: эксплуатация очередей проще.

- **Event discovery как Symfony/Laravel**
  - **Что добавить**: autodiscovery listeners/subscribers по атрибутам/конвенциям.
  - **Зачем**: меньше регистрации вручную.

---

## Архитектура пакетов и совместимость (Laminas/Zend)

- **Отдельные компоненты как Laminas**
  - **Что добавить**: строгие границы пакетов, минимальные зависимости, contracts.
  - **Зачем**: можно использовать как набор библиотек.

- **PSR-first политика**
  - **Что добавить**: чёткие контракты (PSR-3/6/16/14/11/7/15), адаптеры к популярным имплементациям.
  - **Зачем**: максимальная совместимость экосистемы.

---

## Производительность (Phalcon/Spiral)

- **Preloading / opcache-friendly**
  - **Что добавить**: preloading список классов, “compiled” routes/container, минимизация reflection.
  - **Зачем**: скорость в production.

- **RoadRunner/FrankenPHP “режим воркеров”**
  - **Что добавить**: state reset hooks, memory leak guards, request-scoped services.
  - **Зачем**: корректность в long-running окружениях.

---

## Приоритетный план (предложение)

- **P0 (сильно влияет на DX/качество)**: profiler/debugbar (SQL/cache/events), config/env layer, route model binding, container dump.
- **P1 (расширяемость)**: Symfony-style events lifecycle, constraints validator, autodiscovery listeners.
- **P2 (экосистема)**: альтернативный renderer (Twig), OTel hooks, horizon-like queue UI.

