# Компоненты Nextphp

Ниже краткая документация по всем пакетам монорепозитория.

## Реализованные компоненты

- `nextphp/core`: DI-контейнер, service providers, autowiring.
- `nextphp/http`: PSR-7 сообщения, фабрики, middleware pipeline.
- `nextphp/routing`: Router, RadixTree, resource routes, URL generator.
- `nextphp/orm`: QueryBuilder, модели, связи, миграции, SQL + Mongo/ClickHouse соединения.
- `nextphp/migrations`: отдельный пакет миграций и schema builder (BC-слой сохранен в ORM).
- `nextphp/cache`: PSR-16 cache, TTL, tags, remember.
- `nextphp/events`: PSR-14 dispatcher, listeners/subscribers.
- `nextphp/validation`: rule-based validator, built-in и custom rules.
- `nextphp/queue`: in-memory queue, delayed jobs, worker, retry.
- `nextphp/octane`: runtime integration for Swoole/RoadRunner and long-running workers.
- `nextphp/core` async runtime: FiberScheduler + async tasks API for cooperative concurrency.
- `nextphp/websocket`: websocket server lifecycle, broadcast API, Ratchet/Swoole adapters.
- `nextphp/graphql`: schema registry and lightweight query executor for root fields.
- `nextphp/view`: blade-like directives (`{{ }}`, `@if`, `@foreach`, `@include`).
- `nextphp/console`: command app, output helper, `make:*` generator, installer.
- `nextphp/auth`: session/token guards, gates, policies, auth middleware.
- `nextphp/filesystem`: local storage API, file streams, URL generation.
- `nextphp/mail`: array mailer, queued mailer, SMTP mailer.
- `nextphp/testing`: test response asserts, HTTP client, routing-integrated client.

## Быстрые ссылки

- Установка: `docs/getting-started/installation.md`
- Архитектура: `docs/architecture/overview.md`
- Шаблоны приложений и installer: `docs/ecosystem/skeletons-and-installer.md`
