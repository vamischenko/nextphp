# Nextphp Framework

[![CI](https://github.com/nextphp/nextphp/actions/workflows/ci.yml/badge.svg)](https://github.com/nextphp/nextphp/actions/workflows/ci.yml)
[![Static Analysis](https://github.com/nextphp/nextphp/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/nextphp/nextphp/actions/workflows/static-analysis.yml)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Nextphp — современный PHP-фреймворк в формате **monorepo**: набор независимых компонентов (packages) + шаблоны приложений.

## Философия

- **PSR-first**: PSR-7/11/14/15 и др.
- **Компонентный подход**: пакеты можно использовать отдельно.
- **Сильный DX**: консоль, генераторы, шаблоны приложений.
- **Продакшн-готовность**: статический анализ (PHPStan/Psalm), тесты, CI.

## Требования

- PHP 8.2+
- Composer 2.x

## Быстрый старт (шаблоны)

В репозитории есть готовые шаблоны:
- `templates/skeleton` — базовое приложение
- `templates/api-skeleton` — API-приложение

Установить шаблон можно через локальный installer:

```bash
composer install
php bin/nextphp project:install skeleton /path/to/my-app
# или
php bin/nextphp project:install api-skeleton /path/to/my-api
```

## Разработка фреймворка (monorepo)

```bash
git clone https://github.com/nextphp/nextphp.git
cd nextphp
composer install
composer qa
```

Подробности: `docs/getting-started/installation.md`

## Пакеты

| Пакет | Описание |
|---|---|
| `nextphp/core` | IoC Container, Service Providers, compiler passes |
| `nextphp/http` | PSR-7/PSR-15 слой, middleware pipeline, debugbar middleware |
| `nextphp/routing` | RadixTree Router, attribute routes, fluent API, rate limiting |
| `nextphp/orm` | Active Record ORM, миграции, сидеры, фабрики, связи (в т.ч. `morphTo`) |
| `nextphp/migrations` | Миграции и откаты |
| `nextphp/view` | Blade-like шаблонизатор (директивы, компоненты, слоты) |
| `nextphp/console` | CLI и генераторы + installer шаблонов |
| `nextphp/cache` | Cache (PSR-16), драйверы (array/file/redis/memcached/DB) |
| `nextphp/queue` | Очереди/worker, retry/delay, batching, failed jobs store |
| `nextphp/events` | Event dispatcher (PSR-14) + async dispatcher |
| `nextphp/auth` | Guards, gates/policies, remember-me, password reset, TOTP |
| `nextphp/validation` | Rule-based + attribute validation + локализация ошибок |
| `nextphp/filesystem` | Local/S3/FTP/SFTP + streams + Flysystem adapters |
| `nextphp/mail` | SMTP + SES/Mailgun/Postmark + Mailable |
| `nextphp/testing` | TestCase, HTTP testing, snapshots, mocking, browser testing (Panther) |
| `nextphp/octane` | Runtime bridge (Swoole/RoadRunner) |
| `nextphp/websocket` | WebSocket runtime (адаптеры) |
| `nextphp/graphql` | GraphQL слой |
| `nextphp/log` | PSR-3 logger пакет |

## Качество и команды

```bash
composer install

# Всё (стиль + анализаторы + тесты)
composer qa

# Тесты
composer test

# PHPStan
composer analyse

# Psalm
composer psalm

# Стиль
composer cs:check

composer cs:fix
```

## Runtime / интеграции (демо-скрипты)

- WebSocket demo: `php bin/ws-server swoole` или `php bin/ws-server ratchet`  
  Док: `docs/integrations/websocket.md`
- Octane demo: `php bin/octane-server swoole` или `php bin/octane-server roadrunner`  
  Док: `docs/integrations/octane.md`
- GraphQL integration: `docs/integrations/graphql.md`

## Документация

- Индекс: `docs/index.md`
- Архитектура: `docs/architecture/overview.md`
- Совместимость: `docs/compatibility-matrix.md`
- Шаблоны и installer: `docs/ecosystem/skeletons-and-installer.md`

## Стандарты

- PHP 8.2+ with strict types
- PSR-1, PSR-4, PSR-7, PSR-11, PSR-12, PSR-14, PSR-15
- PHPStan level 8
- Psalm level 1
- CI workflows: tests, code style, static analysis

## License

MIT
