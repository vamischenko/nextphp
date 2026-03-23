# Архитектура

## Monorepo

Nextphp — это monorepo из 14 независимых Composer-пакетов. Каждый пакет можно использовать отдельно.

```
packages/
├── core/       nextphp/core
├── http/       nextphp/http
├── routing/    nextphp/routing
├── orm/        nextphp/orm
└── ...
```

## Жизненный цикл запроса

```
index.php
  └── Application::boot()
        └── ServiceProviders::register() + boot()
              └── HttpKernel::handle(Request)
                    └── MiddlewarePipeline
                          └── Router::match()
                                └── Controller::action()
                                      └── Response
```

## IoC Container

Центральный компонент фреймворка. Все сервисы регистрируются через Service Providers и разрешаются через Container с autowiring.

## Стандарты

Полное соответствие PSR:

| PSR | Описание |
|-----|----------|
| PSR-4 | Autoloading |
| PSR-7 | HTTP Message Interfaces |
| PSR-11 | Container Interface |
| PSR-12 | Extended Coding Style |
| PSR-14 | Event Dispatcher |
| PSR-15 | HTTP Server Handlers |
