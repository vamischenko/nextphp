# Установка

## Системные требования

- PHP 8.2+
- Composer 2.x
- PDO (опционально, для ORM)
- Расширения: `mbstring`, `openssl`, `json`, `tokenizer`, `xml`, `ctype`, `bcmath`

## Через Composer (skeleton app)

```bash
composer create-project nextphp/skeleton my-app
cd my-app
php nextphp serve
```

## Установка из монорепозитория (для разработки фреймворка)

```bash
git clone https://github.com/nextphp/nextphp.git
cd nextphp
composer install
```

Проверка окружения:

```bash
php -v
composer --version
composer test
```

## Шаблоны проекта

В репозитории доступны готовые шаблоны:

- `templates/skeleton` — веб-приложение;
- `templates/api-skeleton` — API-приложение.

Они уже содержат базовую Vite-интеграцию:

- `package.json`
- `vite.config.js`
- `resources/assets/*`

## Installer

Для создания проекта программно используется installer из `nextphp/console`:

- `Nextphp\Console\Installer\ProjectInstaller`
- `Nextphp\Console\Installer\InstallProjectCommand`

## Запуск runtime bootstrap примеров

После установки зависимостей в монорепозитории:

```bash
# WebSocket bootstrap
php bin/ws-server swoole
php bin/ws-server ratchet

# Octane bootstrap
php bin/octane-server swoole
php bin/octane-server roadrunner
```

Эти скрипты демонстрационные, но повторяют реальный lifecycle и точки интеграции.

## Установка отдельных компонентов

```bash
# Только IoC Container
composer require nextphp/core

# HTTP Layer
composer require nextphp/http

# Маршрутизатор
composer require nextphp/routing

# GraphQL + HTTP endpoint bridge
composer require nextphp/graphql nextphp/http nextphp/routing

# Octane runtime bridge
composer require nextphp/octane nextphp/core nextphp/http
```

## Структура проекта

```
my-app/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── bootstrap/
├── config/
├── public/
│   └── index.php
├── routes/
│   └── web.php
├── storage/
└── nextphp
```
