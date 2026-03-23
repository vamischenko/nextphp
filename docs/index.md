# Nextphp Framework

**Nextphp** — современный PHP-фреймворк, объединяющий лучшее из трёх миров:

| Источник | Что взяли |
|----------|-----------|
| **Laravel** | DX, Eloquent ORM, Artisan CLI, Facades, Blade |
| **Yii2** | Производительность, lazy loading, batch operations |
| **Symfony** | DI Container, EventDispatcher, строгая архитектура |

## Ключевые принципы

- **Convention over Configuration** — умные умолчания, тонкая настройка при необходимости
- **Performance First** — компоненты проектируются с учётом производительности
- **Строгость PSR** — полное соответствие PSR-1, 4, 7, 11, 12, 14, 15

## Требования

- PHP 8.2+
- Composer 2.x

## Быстрый старт

```bash
composer create-project nextphp/skeleton my-app
cd my-app
php nextphp serve
```

## Quick Start Runtime

```bash
# В корне монорепозитория
composer install

# WebSocket demo
php bin/ws-server swoole

# Octane demo
php bin/octane-server swoole
# или
php bin/octane-server roadrunner
```

## Компоненты

- `nextphp/core` — IoC Container, Service Providers
- `nextphp/http` — PSR-7/PSR-15 Request/Response/Middleware
- `nextphp/routing` — RadixTree Router, PHP Attributes
- `nextphp/orm` — Active Record ORM
- `nextphp/view` — Шаблонизатор (Blade-like)
- `nextphp/console` — CLI с генераторами
- ...и ещё 8 компонентов

## Документация

- [Версии документации](v0.x/index.md)
- [Compatibility Matrix](compatibility-matrix.md)
- [Обзор архитектуры](architecture/overview.md)
- [Установка](getting-started/installation.md)
- [Компоненты](components/index.md)
- [GraphQL интеграция](integrations/graphql.md)
- [WebSocket bootstrap](integrations/websocket.md)
- [Octane bridge](integrations/octane.md)
- [Skeletons и Installer](ecosystem/skeletons-and-installer.md)
